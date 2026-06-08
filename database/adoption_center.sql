CREATE DATABASE IF NOT EXISTS pawhouse_adoption
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pawhouse_adoption;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    password_visible_to_admin VARCHAR(100) NOT NULL,
    role ENUM('client', 'employee', 'admin') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    phone VARCHAR(40) NOT NULL,
    housing_type VARCHAR(80) NOT NULL,
    became_client_at DATE NOT NULL,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    age INT NOT NULL,
    role_title VARCHAR(100) NOT NULL,
    started_working DATE NOT NULL,
    quitting_date DATE NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE animal_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(500) NULL
);

CREATE TABLE animal_breeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    image_url VARCHAR(500) NULL,
    fact TEXT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES animal_categories(id) ON DELETE CASCADE
);

CREATE TABLE animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    breed_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    age_months INT NOT NULL,
    sex ENUM('male', 'female', 'unknown') NOT NULL DEFAULT 'unknown',
    image_url VARCHAR(500) NULL,
    former_state ENUM('home', 'street', 'other_shelter') NOT NULL,
    health_state VARCHAR(160) NOT NULL,
    arrival_date DATE NOT NULL,
    adoption_state ENUM('available', 'reserved', 'adopted', 'returned') NOT NULL DEFAULT 'available',
    adopted_at DATE NULL,
    returned_at DATE NULL,
    notes TEXT NULL,
    FOREIGN KEY (category_id) REFERENCES animal_categories(id),
    FOREIGN KEY (breed_id) REFERENCES animal_breeds(id)
);

CREATE TABLE adoption_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    animal_id INT NOT NULL,
    handled_by_employee_id INT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'delivered', 'returned') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    delivered_at DATETIME NULL,
    requirements_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
    employee_note TEXT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (animal_id) REFERENCES animals(id),
    FOREIGN KEY (handled_by_employee_id) REFERENCES employees(id)
);

CREATE TABLE return_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adoption_request_id INT NOT NULL,
    reason TEXT NOT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open', 'accepted', 'refused', 'completed') NOT NULL DEFAULT 'open',
    resolved_at DATETIME NULL,
    FOREIGN KEY (adoption_request_id) REFERENCES adoption_requests(id) ON DELETE CASCADE
);

CREATE TABLE surrender_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    category_id INT NOT NULL,
    race VARCHAR(100) NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    age VARCHAR(40) NOT NULL,
    sex ENUM('Male', 'Female', 'Unknown') NOT NULL DEFAULT 'Unknown',
    image_path VARCHAR(500) NOT NULL DEFAULT '',
    info TEXT NOT NULL,
    dropoff_date DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES animal_categories(id)
);

CREATE TABLE follow_up_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adoption_request_id INT NOT NULL,
    employee_id INT NOT NULL,
    meeting_number TINYINT NOT NULL,
    scheduled_for DATE NOT NULL,
    completed_at DATETIME NULL,
    animal_condition VARCHAR(160) NULL,
    treatment_notes TEXT NULL,
    return_required BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (adoption_request_id) REFERENCES adoption_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT chk_meeting_number CHECK (meeting_number BETWEEN 1 AND 3)
);

INSERT INTO users (full_name, email, password_hash, password_visible_to_admin, role) VALUES
('Admin pawHouse', 'admin@pawhouse.test', '$6$pawhouse$0Ge8vYwZc1Nf.8lTjVPHP5C52g9hVyRMJyMqOsBJgMoOJnHm2wBNHlt4ksv4dkMH9XP8pcjOIoDmF7/6kQXhF/', '1234', 'admin'),
('Salma Berrada', 'salma@pawhouse.test', '$6$pawhouse$0Ge8vYwZc1Nf.8lTjVPHP5C52g9hVyRMJyMqOsBJgMoOJnHm2wBNHlt4ksv4dkMH9XP8pcjOIoDmF7/6kQXhF/', '1234', 'employee'),
('Youssef Amrani', 'youssef@pawhouse.test', '$6$pawhouse$0Ge8vYwZc1Nf.8lTjVPHP5C52g9hVyRMJyMqOsBJgMoOJnHm2wBNHlt4ksv4dkMH9XP8pcjOIoDmF7/6kQXhF/', '1234', 'employee'),
('Nadia El Fassi', 'nadia@example.test', '$6$pawhouse$0Ge8vYwZc1Nf.8lTjVPHP5C52g9hVyRMJyMqOsBJgMoOJnHm2wBNHlt4ksv4dkMH9XP8pcjOIoDmF7/6kQXhF/', '1234', 'client'),
('Karim Mansouri', 'karim@example.test', '$6$pawhouse$0Ge8vYwZc1Nf.8lTjVPHP5C52g9hVyRMJyMqOsBJgMoOJnHm2wBNHlt4ksv4dkMH9XP8pcjOIoDmF7/6kQXhF/', '1234', 'client'),
('Hiba Saidi', 'hiba@example.test', '$6$pawhouse$0Ge8vYwZc1Nf.8lTjVPHP5C52g9hVyRMJyMqOsBJgMoOJnHm2wBNHlt4ksv4dkMH9XP8pcjOIoDmF7/6kQXhF/', '1234', 'client');

INSERT INTO clients (user_id, phone, housing_type, became_client_at, notes) VALUES
(4, '+212 600 100 101', 'Apartment', '2023-05-14', 'Excellent follow-up history.'),
(5, '+212 600 100 102', 'House with garden', '2024-02-02', 'One previous return accepted after allergy report.'),
(6, '+212 600 100 103', 'Shared family home', '2025-01-19', 'Meeting notes positive.');

INSERT INTO employees (user_id, age, role_title, started_working, quitting_date, active) VALUES
(2, 34, 'Adoption Manager', '2019-03-12', NULL, TRUE),
(3, 29, 'Animal Care Specialist', '2021-08-04', NULL, TRUE);

INSERT INTO animal_categories (slug, name, description, image_url) VALUES
('cats', 'Cats', 'Calm companions, playful kittens, and adult cats ready for quiet homes.', 'https://commons.wikimedia.org/wiki/Special:FilePath/Domestic%20cat.jpg'),
('dogs', 'Dogs', 'Young dogs, trained adults, and senior dogs looking for patient families.', 'https://commons.wikimedia.org/wiki/Special:FilePath/Chocolate%20Labrador%20Retriever.jpg'),
('birds', 'Birds', 'Small birds and parrots that need clean cages, attention, and daily routine.', 'https://commons.wikimedia.org/wiki/Special:FilePath/Little%20Angels%20BUDGERIGAR.jpg'),
('fish', 'Fish', 'Freshwater fish with clear tank requirements and beginner-friendly care notes.', 'https://commons.wikimedia.org/wiki/Special:FilePath/Common%20goldfish.JPG'),
('exclusive', 'Exotic Animals', 'Snakes, spiders, and lizards placed only with prepared and approved adopters.', 'https://commons.wikimedia.org/wiki/Special:FilePath/Cornsnake.jpg');

INSERT INTO animal_breeds (category_id, slug, name, image_url, fact) VALUES
(1, 'maine-coon', 'Maine Coon', 'https://commons.wikimedia.org/wiki/Special:FilePath/Maine%20Coon%20Cat%20Atticus.jpg', 'Large, social cats that need brushing several times per week.'),
(1, 'siamese', 'Siamese', 'https://commons.wikimedia.org/wiki/Special:FilePath/Siam%20lilacpoint.jpg', 'Very vocal and bonded cats that do best with daily interaction.'),
(1, 'domestic-short-hair', 'Domestic Short Hair', 'https://commons.wikimedia.org/wiki/Special:FilePath/Domestic%20shorthair%20cat.jpg', 'Adaptable cats with varied personalities and simple grooming needs.'),
(2, 'labrador', 'Labrador', 'https://commons.wikimedia.org/wiki/Special:FilePath/Chocolate%20Labrador%20Retriever.jpg', 'Friendly and energetic dogs that need exercise and training.'),
(2, 'belgian-malinois', 'Belgian Malinois', 'https://commons.wikimedia.org/wiki/Special:FilePath/Belgian%20Malinois.jpg', 'Intelligent working dogs for experienced owners only.'),
(2, 'mixed-rescue', 'Mixed Rescue', 'https://commons.wikimedia.org/wiki/Special:FilePath/Canis%20lupus%20familiaris%20Perro%20Mestizo.JPG', 'Resilient dogs with individual care histories and flexible temperaments.'),
(3, 'budgie', 'Budgie', 'https://commons.wikimedia.org/wiki/Special:FilePath/Little%20Angels%20BUDGERIGAR.jpg', 'Social birds that need cage cleaning, toys, and gentle handling.'),
(3, 'cockatiel', 'Cockatiel', 'https://commons.wikimedia.org/wiki/Special:FilePath/Cockatiel%20Bird%201.jpg', 'Affectionate birds that enjoy routine and calm homes.'),
(3, 'canary', 'Canary', 'https://commons.wikimedia.org/wiki/Special:FilePath/Canary%20Bird%20Show5.jpg', 'Quiet birds that need stable light, temperature, and nutrition.'),
(4, 'betta', 'Betta', 'https://commons.wikimedia.org/wiki/Special:FilePath/DVJ%20Betta%20splendens%20008.jpg', 'Solitary fish that need warm, filtered tanks.'),
(4, 'goldfish', 'Goldfish', 'https://commons.wikimedia.org/wiki/Special:FilePath/Common%20goldfish.JPG', 'Long-lived fish that need more space than small bowls provide.'),
(4, 'guppy', 'Guppy', 'https://commons.wikimedia.org/wiki/Special:FilePath/The%20guppy%20%2851713178218%29.jpg', 'Colorful schooling fish that do well in planted tanks.'),
(5, 'corn-snake', 'Corn Snake', 'https://commons.wikimedia.org/wiki/Special:FilePath/Cornsnake.jpg', 'Calm snakes that need secure habitats and correct heating.'),
(5, 'bearded-dragon', 'Bearded Dragon', 'https://commons.wikimedia.org/wiki/Special:FilePath/Bearded%20dragon%20Ryuu.jpg', 'Lizards that need UVB lighting, heat gradients, and varied diet.'),
(5, 'tarantula', 'Tarantula', 'https://commons.wikimedia.org/wiki/Special:FilePath/Tarantula%20spider.jpg', 'Low-interaction animals for adopters comfortable with enclosure care.');

INSERT INTO animals (category_id, breed_id, name, age_months, sex, image_url, former_state, health_state, arrival_date, adoption_state, adopted_at, returned_at, notes) VALUES
(1, 1, 'Atlas', 36, 'male', 'https://commons.wikimedia.org/wiki/Special:FilePath/Maine%20Coon%20Cat%20Atticus.jpg', 'other_shelter', 'Vaccinated, calm, needs brushing', '2026-03-05', 'reserved', NULL, NULL, 'Good with adults.'),
(1, 2, 'Nora', 12, 'female', 'https://commons.wikimedia.org/wiki/Special:FilePath/Siam%20lilacpoint.jpg', 'street', 'Vaccinated, shy at first', '2026-04-11', 'available', NULL, NULL, 'Needs quiet introduction.'),
(2, 4, 'Moka', 12, 'female', 'https://commons.wikimedia.org/wiki/Special:FilePath/Chocolate%20Labrador%20Retriever.jpg', 'home', 'Healthy, basic training', '2026-02-17', 'adopted', '2026-05-18', NULL, 'Follow-up meetings active.'),
(2, 6, 'Rio', 6, 'male', 'https://commons.wikimedia.org/wiki/Special:FilePath/Canis%20lupus%20familiaris%20Perro%20Mestizo.JPG', 'street', 'Healthy puppy', '2026-05-02', 'available', NULL, NULL, 'Needs training plan.'),
(4, 10, 'Ruby', 8, 'male', 'https://commons.wikimedia.org/wiki/Special:FilePath/DVJ%20Betta%20splendens%20008.jpg', 'other_shelter', 'Needs heated filtered tank', '2026-01-20', 'available', NULL, NULL, 'No shared tank.'),
(5, 13, 'Copper', 24, 'female', 'https://commons.wikimedia.org/wiki/Special:FilePath/Cornsnake.jpg', 'home', 'Healthy, secure habitat required', '2026-04-22', 'available', NULL, NULL, 'Experienced adopter only.');

INSERT INTO adoption_requests (client_id, animal_id, handled_by_employee_id, status, requested_at, approved_at, delivered_at, requirements_confirmed, employee_note) VALUES
(1, 3, 1, 'delivered', '2026-05-14 10:20:00', '2026-05-16 14:00:00', '2026-05-18 11:30:00', TRUE, 'Home prepared and first food supply checked.'),
(3, 1, 1, 'approved', '2026-05-28 09:10:00', '2026-05-30 16:20:00', NULL, TRUE, 'Delivery scheduled for 2026-06-03.'),
(2, 5, 2, 'under_review', '2026-05-29 12:45:00', NULL, NULL, FALSE, 'Verify tank size before approval.');

INSERT INTO follow_up_meetings (adoption_request_id, employee_id, meeting_number, scheduled_for, completed_at, animal_condition, treatment_notes, return_required) VALUES
(1, 1, 1, '2026-05-25', '2026-05-25 15:00:00', 'Stable and relaxed', 'Eating well, gentle interaction, secure outdoor walks only.', FALSE),
(1, 1, 2, '2026-06-01', NULL, NULL, 'Second meeting scheduled, employee will verify training routine.', FALSE),
(1, 1, 3, '2026-06-08', NULL, NULL, 'Final welfare review pending.', FALSE);
