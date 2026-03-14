-- ============================================================
-- Farm Tracker Pro — Supabase Database Setup Script
-- Run this in: Supabase Dashboard → SQL Editor → New Query
-- ============================================================

-- 1. USER PROFILES TABLE
-- Stores display name, farm name, and profile picture per user.
CREATE TABLE IF NOT EXISTS user_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID UNIQUE NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    display_name TEXT,
    farm_name TEXT DEFAULT 'My Farm',
    email TEXT,
    profile_pic TEXT,  -- base64 image string
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    delegates JSONB DEFAULT '[]'::jsonb -- List of delegate users
);

-- 2. FARM DATA TABLE
-- A unified table for all farm records (livestock, transactions, etc.)
-- Each row = one record in one collection, stored as JSONB.
CREATE TABLE IF NOT EXISTS farm_data (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    collection TEXT NOT NULL,   -- e.g. 'livestock', 'transactions', 'tasks'
    record_id TEXT NOT NULL,    -- the app-generated ID (Date.now().toString())
    data JSONB NOT NULL,        -- the full record object
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(user_id, collection, record_id)  -- enables upsert via merge-duplicates
);

-- 3. INDEXES for fast querying per user per collection
CREATE INDEX IF NOT EXISTS idx_farm_data_user_collection
    ON farm_data(user_id, collection);

-- 4. DISABLE Row Level Security (RLS)
-- The app authenticates users via Supabase Auth tokens.
-- RLS is disabled to keep queries simple.
ALTER TABLE user_profiles DISABLE ROW LEVEL SECURITY;
ALTER TABLE farm_data DISABLE ROW LEVEL SECURITY;

-- 5. RPC for user self-deletion
-- Allows a user to delete their own account.
-- The `ON DELETE CASCADE` on other tables will handle data cleanup.
CREATE OR REPLACE FUNCTION delete_user_account()
RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  DELETE FROM auth.users WHERE id = auth.uid();
END;
$$;

-- 6. MIGRATION (If table exists)
-- Ensure the delegates column exists if the table was already created
ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS delegates JSONB DEFAULT '[]'::jsonb;

-- ============================================================
-- DONE! Your database is ready.
-- Next step: Open index.html in a browser and create your account.
-- ============================================================
