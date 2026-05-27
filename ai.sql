-- 1. Buat Tabel Users
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY, -- Gunakan BIGINT AUTO_INCREMENT jika di MySQL
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Buat Tabel Recipes (Untuk History Generate)
CREATE TABLE recipes (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    prep_time VARCHAR(50) NOT NULL, -- Cth: "15 Menit"
    portions VARCHAR(50),           -- Cth: "2 Porsi"
    difficulty VARCHAR(50),         -- Cth: "Mudah"
    ingredients JSON NOT NULL,      -- Menyimpan array bahan dari AI
    instructions JSON NOT NULL,     -- Menyimpan array langkah dari AI
    ai_generated_time VARCHAR(50),  -- Opsional: cth "0.4s" sesuai di UI desainmu
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relasi: Jika user dihapus, history resepnya ikut terhapus (Cascade)
    CONSTRAINT fk_user_recipe 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE
);

-- 3. Buat Tabel Saved Recipes (Untuk Resep yang di-Bookmark)
CREATE TABLE saved_recipes (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    recipe_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relasi ke tabel Users
    CONSTRAINT fk_saved_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE,
        
    -- Relasi ke tabel Recipes
    CONSTRAINT fk_saved_recipe 
        FOREIGN KEY (recipe_id) 
        REFERENCES recipes(id) 
        ON DELETE CASCADE,
        
    -- Validasi: Mencegah user menyimpan resep yang sama berkali-kali
    CONSTRAINT unique_user_recipe 
        UNIQUE (user_id, recipe_id)
);