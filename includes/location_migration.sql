-- Location pinning migration
-- Run this in phpMyAdmin on your pet_adoption database

USE pet_adoption;

ALTER TABLE pets 
ADD COLUMN latitude DECIMAL(10, 8) NULL,
ADD COLUMN longitude DECIMAL(11, 8) NULL,
ADD COLUMN area VARCHAR(100) NULL;
