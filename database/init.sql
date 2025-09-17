-- PostgreSQL初期化スクリプト
-- データベース作成時に実行される

-- 拡張機能の有効化（UUID生成用）
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- サンプルテーブルの作成（必要に応じて調整してください）
-- CREATE TABLE IF NOT EXISTS sample_table (
--     id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
--     updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
-- );

-- インデックスの作成例
-- CREATE INDEX IF NOT EXISTS idx_sample_table_name ON sample_table(name);

-- プロジェクト固有のテーブル作成はここに追加してください 