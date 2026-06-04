

USE pet_adoption;

-- 1. Add 'status' column to pets table
ALTER TABLE pets
ADD COLUMN status ENUM('active','adopted','removed') NOT NULL DEFAULT 'active'
AFTER is_adopted;

-- 2. Migrate existing is_adopted values into the new status column
UPDATE pets
SET status = CASE
    WHEN is_adopted = 1 THEN 'adopted'
    ELSE 'active'
END;

-- 3. Create pet_photos table (multi-photo support)
CREATE TABLE IF NOT EXISTS pet_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
);

-- 4. Migrate existing single images into pet_photos
INSERT INTO pet_photos (pet_id, photo_path, is_primary)
SELECT id, pet_image, TRUE
FROM pets
WHERE pet_image IS NOT NULL
  AND pet_image != '';

-- 5. Create reports table
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL,
    pet_id INT NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
);
