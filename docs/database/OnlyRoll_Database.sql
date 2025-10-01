-- ===================================
-- OnlyRoll - SCHÉMA BDD
-- Architecture relationnelle pure avec JSON minimal
-- MySQL 8.0+ - Compatible avec Symfony/Doctrine
-- ===================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ===================================
-- TABLES DE RÉFÉRENCE (LOOKUPS)
-- ===================================

-- Types de dégâts
CREATE TABLE damage_type (
    damage_type_id INT(11) NOT NULL AUTO_INCREMENT,
    damage_type_name VARCHAR(50) NOT NULL COMMENT "slashing, piercing, fire, etc.",
    damage_type_category VARCHAR(20) NOT NULL COMMENT "physical, elemental, etc.",
    damage_type_description TEXT NULL,
    PRIMARY KEY (damage_type_id),
    UNIQUE KEY uk_damage_type_name (damage_type_name),
    KEY idx_damage_type_category (damage_type_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Propriétés d'armes
CREATE TABLE weapon_property (
    property_id INT(11) NOT NULL AUTO_INCREMENT,
    property_name VARCHAR(50) NOT NULL COMMENT "finesse, light, heavy, etc.",
    property_abbreviation VARCHAR(10) NOT NULL COMMENT "F, L, H, etc.",
    property_description TEXT NOT NULL,
    PRIMARY KEY (property_id),
    UNIQUE KEY uk_property_name (property_name),
    UNIQUE KEY uk_property_abbreviation (property_abbreviation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types d'objets
CREATE TABLE item_category (
    category_id INT(11) NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL COMMENT "Weapon, Armor, Wondrous, etc.",
    category_description TEXT NULL,
    PRIMARY KEY (category_id),
    UNIQUE KEY uk_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Raretés d'objets
CREATE TABLE item_rarity (
    rarity_id INT(11) NOT NULL AUTO_INCREMENT,
    rarity_name VARCHAR(20) NOT NULL COMMENT "common, uncommon, rare, etc.",
    rarity_color VARCHAR(7) NOT NULL DEFAULT "#3f3f3f" COMMENT "Couleur pour l'UI",
    rarity_order INT(2) NOT NULL DEFAULT "0" COMMENT "Ordre de tri (0=common, 5=legendary)",
    PRIMARY KEY (rarity_id),
    UNIQUE KEY uk_rarity_name (rarity_name),
    KEY idx_rarity_order (rarity_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tailles de créatures
CREATE TABLE creature_size (
    size_id INT(11) NOT NULL AUTO_INCREMENT,
    size_name VARCHAR(20) NOT NULL COMMENT "Tiny, Small, Medium, Large, Huge, Gargantuan",
    size_abbreviation VARCHAR(5) NOT NULL COMMENT "T, S, M, L, H, G",
    size_space_feet INT(2) NOT NULL COMMENT "Espace occupé en pieds",
    size_space_squares INT(2) NOT NULL COMMENT "Espace occupé en cases",
    PRIMARY KEY (size_id),
    UNIQUE KEY uk_size_name (size_name),
    UNIQUE KEY uk_size_abbreviation (size_abbreviation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types de créatures
CREATE TABLE creature_type (
    type_id INT(11) NOT NULL AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL COMMENT "humanoid, undead, dragon, etc.",
    type_description TEXT NULL,
    PRIMARY KEY (type_id),
    UNIQUE KEY uk_type_name (type_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alignements
CREATE TABLE alignment (
    alignment_id INT(11) NOT NULL AUTO_INCREMENT,
    alignment_name VARCHAR(50) NOT NULL COMMENT "lawful good, neutral, chaotic evil, etc.",
    alignment_abbreviation VARCHAR(10) NOT NULL COMMENT "LG, N, CE, etc.",
    alignment_law_chaos VARCHAR(10) NOT NULL COMMENT "lawful, neutral, chaotic",
    alignment_good_evil VARCHAR(10) NOT NULL COMMENT "good, neutral, evil",
    PRIMARY KEY (alignment_id),
    UNIQUE KEY uk_alignment_name (alignment_name),
    UNIQUE KEY uk_alignment_abbreviation (alignment_abbreviation),
    KEY idx_alignment_axes (alignment_law_chaos, alignment_good_evil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Écoles de magie
CREATE TABLE spell_school (
    school_id INT(11) NOT NULL AUTO_INCREMENT,
    school_name VARCHAR(50) NOT NULL COMMENT "Abjuration, Conjuration, etc.",
    school_description TEXT NULL,
    school_color VARCHAR(7) DEFAULT "#6366f1" COMMENT "Couleur pour l'UI",
    PRIMARY KEY (school_id),
    UNIQUE KEY uk_school_name (school_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conditions/États
CREATE TABLE condition_type (
    condition_id INT(11) NOT NULL AUTO_INCREMENT,
    condition_name VARCHAR(50) NOT NULL COMMENT "blinded, charmed, exhaustion, etc.",
    condition_description TEXT NOT NULL,
    condition_icon VARCHAR(100) NULL,
    PRIMARY KEY (condition_id),
    UNIQUE KEY uk_condition_name (condition_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compétences
CREATE TABLE skill (
    skill_id INT(11) NOT NULL AUTO_INCREMENT,
    skill_name VARCHAR(50) NOT NULL COMMENT "Acrobatics, Animal Handling, etc.",
    skill_ability VARCHAR(3) NOT NULL COMMENT "STR, DEX, CON, INT, WIS, CHA",
    skill_description TEXT NULL,
    PRIMARY KEY (skill_id),
    UNIQUE KEY uk_skill_name (skill_name),
    KEY idx_skill_ability (skill_ability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Langues
CREATE TABLE language (
    language_id INT(11) NOT NULL AUTO_INCREMENT,
    language_name VARCHAR(50) NOT NULL COMMENT "Common, Elvish, Draconic, etc.",
    language_type VARCHAR(20) NOT NULL COMMENT "standard, exotic",
    language_script VARCHAR(50) NULL COMMENT "Alphabet utilisé",
    PRIMARY KEY (language_id),
    UNIQUE KEY uk_language_name (language_name),
    KEY idx_language_type (language_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sources de contenu
CREATE TABLE content_source (
    source_id INT(11) NOT NULL AUTO_INCREMENT,
    source_abbreviation VARCHAR(10) NOT NULL COMMENT "PHB, DMG, MM, SCAG, etc.",
    source_full_name VARCHAR(250) NOT NULL,
    source_type VARCHAR(50) NOT NULL COMMENT "core, supplement, adventure",
    source_release_date DATE NULL,
    source_is_official BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (source_id),
    UNIQUE KEY uk_source_abbreviation (source_abbreviation),
    KEY idx_source_type (source_type),
    KEY idx_source_official (source_is_official)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLES UTILISATEUR ET AUTHENTIFICATION
-- ===================================

-- Utilisateurs
CREATE TABLE user (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    user_pseudo VARCHAR(50) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_roles JSON NOT NULL COMMENT "Rôles Symfony [ROLE_USER, ROLE_ADMIN]",
    user_is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    user_avatar VARCHAR(255) NULL,
    user_timezone VARCHAR(50) DEFAULT "UTC",
    user_language VARCHAR(5) DEFAULT "en",
    user_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_last_login DATETIME NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY uk_user_pseudo (user_pseudo),
    UNIQUE KEY uk_user_email (user_email),
    KEY idx_user_verified (user_is_verified),
    KEY idx_user_created (user_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions utilisateur
CREATE TABLE user_session (
    session_id VARCHAR(255) NOT NULL,
    user_id INT(11) NOT NULL,
    session_data LONGTEXT NOT NULL,
    session_lifetime INT(11) NOT NULL,
    session_time INT(11) NOT NULL,
    PRIMARY KEY (session_id),
    KEY idx_user_id (user_id),
    KEY idx_session_time (session_time),
    CONSTRAINT fk_user_session_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLES DE JEU
-- ===================================

-- Parties de jeu
CREATE TABLE game (
    game_id INT(11) NOT NULL AUTO_INCREMENT,
    game_name VARCHAR(250) NOT NULL,
    game_description TEXT NULL,
    game_master_id INT(11) NOT NULL COMMENT "Maître de jeu",
    game_status ENUM("preparation", "active", "paused", "archived") NOT NULL DEFAULT "preparation",
    game_max_players INT(2) NOT NULL DEFAULT 6,
    game_is_public BOOLEAN NOT NULL DEFAULT FALSE,
    game_password VARCHAR(255) NULL,
    game_invite_code VARCHAR(10) NULL UNIQUE,
    game_settings JSON NULL COMMENT "Règles maison, options de partie",
    game_started_at DATETIME NULL,
    game_completed_at DATETIME NULL,
    game_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    game_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (game_id),
    KEY idx_game_master (game_master_id),
    KEY idx_game_status (game_status),
    KEY idx_game_public (game_is_public),
    KEY idx_game_invite_code (game_invite_code),
    KEY idx_game_created (game_created_at),
    CONSTRAINT fk_game_master FOREIGN KEY (game_master_id) REFERENCES user(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Joueurs dans une partie
CREATE TABLE game_player (
    game_player_id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    player_role ENUM("player", "co_gm", "spectator") NOT NULL DEFAULT "player",
    player_status ENUM("invited", "active", "inactive", "banned") NOT NULL DEFAULT "invited",
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME NULL,
    PRIMARY KEY (game_player_id),
    UNIQUE KEY uk_game_player (game_id, user_id),
    KEY idx_user_id (user_id),
    KEY idx_player_role (player_role),
    KEY idx_player_status (player_status),
    CONSTRAINT fk_game_player_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE CASCADE,
    CONSTRAINT fk_game_player_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cartes de jeu
CREATE TABLE game_map (
    map_id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NOT NULL,
    map_name VARCHAR(250) NOT NULL,
    map_description TEXT NULL,
    map_image_url VARCHAR(500) NULL,
    map_grid_size INT(3) NOT NULL DEFAULT 50 COMMENT "Taille de la grille en pixels",
    map_grid_type ENUM("square", "hex", "none") NOT NULL DEFAULT "square",
    map_width INT(4) NOT NULL DEFAULT 20 COMMENT "Largeur en cases",
    map_height INT(4) NOT NULL DEFAULT 20 COMMENT "Hauteur en cases",
    map_is_active BOOLEAN NOT NULL DEFAULT FALSE,
    map_settings JSON NULL COMMENT "Configuration visuelle de la carte",
    map_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    map_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (map_id),
    KEY idx_game_id (game_id),
    KEY idx_map_active (map_is_active),
    CONSTRAINT fk_game_map_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jetons sur la carte
CREATE TABLE game_token (
    token_id INT(11) NOT NULL AUTO_INCREMENT,
    map_id INT(11) NOT NULL,
    token_name VARCHAR(250) NOT NULL,
    token_type ENUM("character", "monster", "npc", "object") NOT NULL,
    character_id INT(11) NULL,
    monster_id INT(11) NULL,
    token_image_url VARCHAR(500) NULL,
    token_x INT(4) NOT NULL DEFAULT 0,
    token_y INT(4) NOT NULL DEFAULT 0,
    token_size DECIMAL(3,1) NOT NULL DEFAULT 1.0 COMMENT "Multiplicateur de taille (1.0 = 1 case)",
    token_rotation INT(3) NOT NULL DEFAULT 0 COMMENT "Rotation en degrés",
    token_is_visible BOOLEAN NOT NULL DEFAULT TRUE,
    token_is_locked BOOLEAN NOT NULL DEFAULT FALSE,
    token_layer VARCHAR(20) NOT NULL DEFAULT "tokens" COMMENT "background, objects, tokens, effects",
    token_settings JSON NULL COMMENT "État visuel du jeton",
    token_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    token_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (token_id),
    KEY idx_map_id (map_id),
    KEY idx_token_type (token_type),
    KEY idx_character_id (character_id),
    KEY idx_monster_id (monster_id),
    KEY idx_token_layer (token_layer),
    CONSTRAINT fk_game_token_map FOREIGN KEY (map_id) REFERENCES game_map(map_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages de chat
CREATE TABLE game_message (
    message_id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    character_id INT(11) NULL COMMENT "Personnage qui parle (si applicable)",
    message_type ENUM("chat", "emote", "whisper", "system", "dice_roll") NOT NULL DEFAULT "chat",
    message_content TEXT NOT NULL,
    message_target_user_id INT(11) NULL COMMENT "Pour les whispers",
    message_dice_result JSON NULL COMMENT "Structure simple des résultats de dés",
    message_is_ic BOOLEAN NOT NULL DEFAULT FALSE COMMENT "In Character",
    message_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_game_id (game_id),
    KEY idx_user_id (user_id),
    KEY idx_character_id (character_id),
    KEY idx_message_type (message_type),
    KEY idx_target_user (message_target_user_id),
    KEY idx_message_created (message_created_at),
    CONSTRAINT fk_game_message_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE CASCADE,
    CONSTRAINT fk_game_message_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_game_message_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE SET NULL,
    CONSTRAINT fk_game_message_target FOREIGN KEY (message_target_user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lancers de dés
CREATE TABLE dice_roll (
    roll_id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NULL,
    user_id INT(11) NOT NULL,
    character_id INT(11) NULL,
    roll_expression VARCHAR(500) NOT NULL COMMENT "Expression originale (ex: 2d6+3)",
    roll_result JSON NOT NULL COMMENT "Détails du lancer",
    roll_total INT(11) NOT NULL,
    roll_type VARCHAR(50) NULL COMMENT "attack, damage, saving_throw, ability_check, etc.",
    roll_context VARCHAR(250) NULL COMMENT "Description du contexte",
    roll_is_private BOOLEAN NOT NULL DEFAULT FALSE,
    roll_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (roll_id),
    KEY idx_game_id (game_id),
    KEY idx_user_id (user_id),
    KEY idx_character_id (character_id),
    KEY idx_roll_type (roll_type),
    KEY idx_roll_created (roll_created_at),
    CONSTRAINT fk_dice_roll_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE CASCADE,
    CONSTRAINT fk_dice_roll_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLES SRD (System Reference Document)
-- ===================================

-- Races
CREATE TABLE srd_race (
    race_id INT(11) NOT NULL AUTO_INCREMENT,
    race_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    race_description TEXT NOT NULL,
    race_size_id INT(11) NOT NULL,
    race_speed INT(2) NOT NULL DEFAULT 30,
    race_darkvision INT(3) NOT NULL DEFAULT 0,
    race_age_description TEXT NULL,
    race_alignment_tendency TEXT NULL,
    race_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    race_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (race_id),
    UNIQUE KEY uk_race_name_source (race_name, source_id),
    KEY idx_source_id (source_id),
    KEY idx_race_size (race_size_id),
    CONSTRAINT fk_srd_race_source FOREIGN KEY (source_id) REFERENCES content_source(source_id),
    CONSTRAINT fk_srd_race_size FOREIGN KEY (race_size_id) REFERENCES creature_size(size_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificateurs de caractéristiques des races
CREATE TABLE race_ability_modifier (
    modifier_id INT(11) NOT NULL AUTO_INCREMENT,
    race_id INT(11) NOT NULL,
    ability_name VARCHAR(3) NOT NULL COMMENT "STR, DEX, CON, INT, WIS, CHA",
    modifier_value INT(2) NOT NULL,
    PRIMARY KEY (modifier_id),
    UNIQUE KEY uk_race_ability (race_id, ability_name),
    CONSTRAINT fk_race_ability_race FOREIGN KEY (race_id) REFERENCES srd_race(race_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Langues des races
CREATE TABLE race_language (
    race_language_id INT(11) NOT NULL AUTO_INCREMENT,
    race_id INT(11) NOT NULL,
    language_id INT(11) NULL,
    language_count INT(2) NULL COMMENT "Nombre de langues au choix si language_id est null",
    PRIMARY KEY (race_language_id),
    KEY idx_race_id (race_id),
    KEY idx_language_id (language_id),
    CONSTRAINT fk_race_language_race FOREIGN KEY (race_id) REFERENCES srd_race(race_id) ON DELETE CASCADE,
    CONSTRAINT fk_race_language_lang FOREIGN KEY (language_id) REFERENCES language(language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Traits raciaux
CREATE TABLE race_trait (
    trait_id INT(11) NOT NULL AUTO_INCREMENT,
    race_id INT(11) NOT NULL,
    trait_name VARCHAR(250) NOT NULL,
    trait_description TEXT NOT NULL,
    trait_type VARCHAR(50) NULL COMMENT "passive, active, spell_like",
    trait_usage VARCHAR(100) NULL COMMENT "at_will, short_rest, long_rest, 1/day, etc.",
    PRIMARY KEY (trait_id),
    KEY idx_race_id (race_id),
    KEY idx_trait_type (trait_type),
    CONSTRAINT fk_race_trait_race FOREIGN KEY (race_id) REFERENCES srd_race(race_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sous-races
CREATE TABLE srd_subrace (
    subrace_id INT(11) NOT NULL AUTO_INCREMENT,
    race_id INT(11) NOT NULL,
    subrace_name VARCHAR(250) NOT NULL,
    subrace_description TEXT NOT NULL,
    subrace_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subrace_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (subrace_id),
    UNIQUE KEY uk_subrace_name_race (subrace_name, race_id),
    KEY idx_race_id (race_id),
    CONSTRAINT fk_srd_subrace_race FOREIGN KEY (race_id) REFERENCES srd_race(race_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificateurs de sous-race
CREATE TABLE subrace_ability_modifier (
    modifier_id INT(11) NOT NULL AUTO_INCREMENT,
    subrace_id INT(11) NOT NULL,
    ability_name VARCHAR(3) NOT NULL,
    modifier_value INT(2) NOT NULL,
    PRIMARY KEY (modifier_id),
    UNIQUE KEY uk_subrace_ability (subrace_id, ability_name),
    CONSTRAINT fk_subrace_ability_subrace FOREIGN KEY (subrace_id) REFERENCES srd_subrace(subrace_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Traits de sous-race
CREATE TABLE subrace_trait (
    trait_id INT(11) NOT NULL AUTO_INCREMENT,
    subrace_id INT(11) NOT NULL,
    trait_name VARCHAR(250) NOT NULL,
    trait_description TEXT NOT NULL,
    trait_type VARCHAR(50) NULL,
    trait_usage VARCHAR(100) NULL,
    PRIMARY KEY (trait_id),
    KEY idx_subrace_id (subrace_id),
    CONSTRAINT fk_subrace_trait_subrace FOREIGN KEY (subrace_id) REFERENCES srd_subrace(subrace_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classes
CREATE TABLE srd_class (
    class_id INT(11) NOT NULL AUTO_INCREMENT,
    class_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    class_description TEXT NOT NULL,
    class_hit_die INT(2) NOT NULL COMMENT "d6, d8, d10, d12",
    class_primary_ability VARCHAR(100) NOT NULL,
    class_skill_count INT(2) NOT NULL DEFAULT 2 COMMENT 'Nombre de compétences à choisir',
    class_spellcasting_ability VARCHAR(3) NULL COMMENT "INT, WIS, CHA ou NULL",
    class_spellcasting_type VARCHAR(20) NULL COMMENT "full, half, third, warlock, etc.",
    class_gold_alternative VARCHAR(20) NULL COMMENT 'Ex: 5d4 × 10 gp',
    class_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    class_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id),
    UNIQUE KEY uk_class_name_source (class_name, source_id),
    KEY idx_source_id (source_id),
    KEY idx_hit_die (class_hit_die),
    CONSTRAINT fk_srd_class_source FOREIGN KEY (source_id) REFERENCES content_source(source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fonctionnalités de classe par niveau
CREATE TABLE class_feature (
    feature_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    feature_level INT(2) NOT NULL,
    feature_name VARCHAR(250) NOT NULL,
    feature_description TEXT NOT NULL,
    feature_type VARCHAR(50) NULL COMMENT "passive, active, spell_slot, etc.",
    feature_order INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (feature_id),
    KEY idx_class_id (class_id),
    KEY idx_feature_level (feature_level),
    KEY idx_feature_order (feature_order),
    CONSTRAINT fk_class_feature_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Progression des sorts par niveau de classe
CREATE TABLE class_spell_slots (
    slot_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    class_level INT(2) NOT NULL,
    spell_level INT(1) NOT NULL COMMENT "1-9",
    slot_count INT(2) NOT NULL,
    PRIMARY KEY (slot_id),
    UNIQUE KEY uk_class_level_spell (class_id, class_level, spell_level),
    CONSTRAINT fk_class_slots_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maîtrise des jets de sauvegardes
CREATE TABLE class_saving_throw_proficiency (
    proficiency_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    ability_name VARCHAR(3) NOT NULL COMMENT "STR, DEX, CON, INT, WIS, CHA",
    PRIMARY KEY (proficiency_id),
    UNIQUE KEY uk_class_saving_throw (class_id, ability_name),
    CONSTRAINT fk_class_saving_throw_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
);

-- Maîtrises d'armures par classe
CREATE TABLE class_armor_proficiency (
    proficiency_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    armor_type VARCHAR(50) NOT NULL COMMENT "light, medium, heavy, shields",
    PRIMARY KEY (proficiency_id),
    UNIQUE KEY uk_class_armor (class_id, armor_type),
    CONSTRAINT fk_class_armor_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maîtrises d'armes par classe
CREATE TABLE class_weapon_proficiency (
    proficiency_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    weapon_category VARCHAR(50) NOT NULL COMMENT "simple, martial ou nom spécifique",
    PRIMARY KEY (proficiency_id),
    UNIQUE KEY uk_class_weapon (class_id, weapon_category),
    CONSTRAINT fk_class_weapon_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maîtrise des outils
CREATE TABLE class_tool_proficiency (
    proficiency_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    tool_name VARCHAR(100) NOT NULL COMMENT "thieves tools, tinker tools, etc.",
    is_choice BOOLEAN NOT NULL DEFAULT FALSE COMMENT "TRUE si choix parmi plusieurs",
    choice_count INT(2) NULL COMMENT "Nombre à choisir si is_choice=TRUE",
    PRIMARY KEY (proficiency_id),
    KEY idx_class_id (class_id),
    CONSTRAINT fk_class_tool_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
);

-- Compétences disponibles par classe
CREATE TABLE class_skill_option (
    option_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    skill_id INT(11) NOT NULL,
    PRIMARY KEY (option_id),
    UNIQUE KEY uk_class_skill (class_id, skill_id),
    CONSTRAINT fk_class_skill_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE,
    CONSTRAINT fk_class_skill_skill FOREIGN KEY (skill_id) REFERENCES skill(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipement de départ
CREATE TABLE class_starting_equipment (
    equipment_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    equipment_type ENUM("weapon", "armor", "tool", "pack", "item", "choice") NOT NULL,
    equipment_name VARCHAR(250) NOT NULL,
    quantity INT(3) NOT NULL DEFAULT 1,
    is_alternative BOOLEAN NOT NULL DEFAULT FALSE COMMENT "Option (a) ou (b)",
    alternative_group INT(2) NULL COMMENT "Groupe d'alternatives",
    equipment_description TEXT NULL,
    PRIMARY KEY (equipment_id),
    KEY idx_class_id (class_id),
    KEY idx_equipment_type (equipment_type),
    CONSTRAINT fk_class_equipment_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
);

-- Sous-classes
CREATE TABLE srd_subclass (
    subclass_id INT(11) NOT NULL AUTO_INCREMENT,
    class_id INT(11) NOT NULL,
    subclass_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    subclass_description TEXT NOT NULL,
    subclass_flavor TEXT NULL,
    subclass_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subclass_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (subclass_id),
    UNIQUE KEY uk_subclass_name_class (subclass_name, class_id),
    KEY idx_class_id (class_id),
    KEY idx_source_id (source_id),
    CONSTRAINT fk_srd_subclass_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE,
    CONSTRAINT fk_srd_subclass_source FOREIGN KEY (source_id) REFERENCES content_source(source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fonctionnalités de sous-classe
CREATE TABLE subclass_feature (
    feature_id INT(11) NOT NULL AUTO_INCREMENT,
    subclass_id INT(11) NOT NULL,
    feature_level INT(2) NOT NULL,
    feature_name VARCHAR(250) NOT NULL,
    feature_description TEXT NOT NULL,
    feature_order INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (feature_id),
    KEY idx_subclass_id (subclass_id),
    KEY idx_feature_level (feature_level),
    KEY idx_feature_order (feature_order),
    CONSTRAINT fk_subclass_feature_subclass FOREIGN KEY (subclass_id) REFERENCES srd_subclass(subclass_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sorts
CREATE TABLE srd_spell (
    spell_id INT(11) NOT NULL AUTO_INCREMENT,
    spell_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    spell_level INT(1) NOT NULL COMMENT "0 (cantrip) à 9",
    school_id INT(11) NOT NULL,
    spell_casting_time VARCHAR(100) NOT NULL,
    spell_range VARCHAR(100) NOT NULL,
    spell_components VARCHAR(50) NOT NULL COMMENT "V, S, M",
    spell_material_components TEXT NULL,
    spell_duration VARCHAR(100) NOT NULL,
    spell_concentration BOOLEAN NOT NULL DEFAULT FALSE,
    spell_ritual BOOLEAN NOT NULL DEFAULT FALSE,
    spell_description TEXT NOT NULL,
    spell_higher_levels TEXT NULL,
    spell_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    spell_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (spell_id),
    UNIQUE KEY uk_spell_name_source (spell_name, source_id),
    KEY idx_source_id (source_id),
    KEY idx_spell_level (spell_level),
    KEY idx_school_id (school_id),
    KEY idx_spell_concentration (spell_concentration),
    KEY idx_spell_ritual (spell_ritual),
    CONSTRAINT fk_srd_spell_source FOREIGN KEY (source_id) REFERENCES content_source(source_id),
    CONSTRAINT fk_srd_spell_school FOREIGN KEY (school_id) REFERENCES spell_school(school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classes disponibles pour chaque sort
CREATE TABLE spell_class (
    spell_class_id INT(11) NOT NULL AUTO_INCREMENT,
    spell_id INT(11) NOT NULL,
    class_id INT(11) NOT NULL,
    PRIMARY KEY (spell_class_id),
    UNIQUE KEY uk_spell_class (spell_id, class_id),
    CONSTRAINT fk_spell_class_spell FOREIGN KEY (spell_id) REFERENCES srd_spell(spell_id) ON DELETE CASCADE,
    CONSTRAINT fk_spell_class_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sorts de sous-classe
CREATE TABLE subclass_spell (
    subclass_spell_id INT(11) NOT NULL AUTO_INCREMENT,
    subclass_id INT(11) NOT NULL,
    spell_id INT(11) NOT NULL,
    spell_level INT(2) NOT NULL COMMENT "Niveau auquel le sort est obtenu",
    is_always_prepared BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (subclass_spell_id),
    UNIQUE KEY uk_subclass_spell (subclass_id, spell_id),
    KEY idx_spell_level (spell_level),
    CONSTRAINT fk_subclass_spell_subclass FOREIGN KEY (subclass_id) REFERENCES srd_subclass(subclass_id) ON DELETE CASCADE,
    CONSTRAINT fk_subclass_spell_spell FOREIGN KEY (spell_id) REFERENCES srd_spell(spell_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dégâts des sorts
CREATE TABLE spell_damage (
    damage_id INT(11) NOT NULL AUTO_INCREMENT,
    spell_id INT(11) NOT NULL,
    damage_type_id INT(11) NOT NULL,
    damage_dice VARCHAR(50) NULL COMMENT "Expression des dés (ex: 3d6)",
    damage_flat INT(3) NULL COMMENT "Dégâts fixes",
    damage_scaling VARCHAR(100) NULL COMMENT "Comment les dégâts évoluent",
    PRIMARY KEY (damage_id),
    KEY idx_spell_id (spell_id),
    KEY idx_damage_type (damage_type_id),
    CONSTRAINT fk_spell_damage_spell FOREIGN KEY (spell_id) REFERENCES srd_spell(spell_id) ON DELETE CASCADE,
    CONSTRAINT fk_spell_damage_type FOREIGN KEY (damage_type_id) REFERENCES damage_type(damage_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objets magiques
CREATE TABLE srd_item (
    item_id INT(11) NOT NULL AUTO_INCREMENT,
    item_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    category_id INT(11) NOT NULL,
    rarity_id INT(11) NOT NULL,
    item_description TEXT NOT NULL,
    item_requires_attunement BOOLEAN NOT NULL DEFAULT FALSE,
    item_attunement_restriction TEXT NULL,
    item_weight DECIMAL(5,2) NULL COMMENT "Poids en livres",
    item_value_gp DECIMAL(10,2) NULL COMMENT "Valeur en pièces d'or",
    item_armor_class INT(2) NULL COMMENT "Pour les armures",
    item_strength_requirement INT(2) NULL COMMENT "Force requise pour porter",
    item_stealth_disadvantage BOOLEAN NOT NULL DEFAULT FALSE,
    item_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    item_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (item_id),
    UNIQUE KEY uk_item_name_source (item_name, source_id),
    KEY idx_source_id (source_id),
    KEY idx_category_id (category_id),
    KEY idx_rarity_id (rarity_id),
    KEY idx_attunement (item_requires_attunement),
    CONSTRAINT fk_srd_item_source FOREIGN KEY (source_id) REFERENCES content_source(source_id),
    CONSTRAINT fk_srd_item_category FOREIGN KEY (category_id) REFERENCES item_category(category_id),
    CONSTRAINT fk_srd_item_rarity FOREIGN KEY (rarity_id) REFERENCES item_rarity(rarity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Propriétés des armes
CREATE TABLE item_weapon_property (
    item_property_id INT(11) NOT NULL AUTO_INCREMENT,
    item_id INT(11) NOT NULL,
    property_id INT(11) NOT NULL,
    property_value VARCHAR(50) NULL COMMENT "Valeur pour les propriétés avec paramètre (ex: reach 10)",
    PRIMARY KEY (item_property_id),
    UNIQUE KEY uk_item_property (item_id, property_id),
    CONSTRAINT fk_item_weapon_item FOREIGN KEY (item_id) REFERENCES srd_item(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_item_weapon_property FOREIGN KEY (property_id) REFERENCES weapon_property(property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dégâts des armes
CREATE TABLE item_weapon_damage (
    damage_id INT(11) NOT NULL AUTO_INCREMENT,
    item_id INT(11) NOT NULL,
    damage_type_id INT(11) NOT NULL,
    damage_dice VARCHAR(20) NOT NULL COMMENT "Expression des dés (ex: 1d6, 2d4)",
    damage_versatile_dice VARCHAR(20) NULL COMMENT "Dégâts polyvalents",
    PRIMARY KEY (damage_id),
    KEY idx_item_id (item_id),
    KEY idx_damage_type (damage_type_id),
    CONSTRAINT fk_item_damage_item FOREIGN KEY (item_id) REFERENCES srd_item(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_item_damage_type FOREIGN KEY (damage_type_id) REFERENCES damage_type(damage_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monstres
CREATE TABLE srd_monster (
    monster_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    size_id INT(11) NOT NULL,
    type_id INT(11) NOT NULL,
    alignment_id INT(11) NULL,
    monster_ac INT(2) NOT NULL,
    monster_ac_source VARCHAR(100) NULL COMMENT "Natural Armor, Mage Armor, etc.",
    monster_hp_average INT(4) NOT NULL,
    monster_hp_dice VARCHAR(50) NOT NULL COMMENT "Expression des PV (ex: 13d8+39)",
    monster_speed_walk INT(3) NOT NULL DEFAULT 30,
    monster_speed_fly INT(3) NULL,
    monster_speed_swim INT(3) NULL,
    monster_speed_climb INT(3) NULL,
    monster_speed_burrow INT(3) NULL,
    monster_str INT(2) NOT NULL,
    monster_dex INT(2) NOT NULL,
    monster_con INT(2) NOT NULL,
    monster_int INT(2) NOT NULL,
    monster_wis INT(2) NOT NULL,
    monster_cha INT(2) NOT NULL,
    monster_cr VARCHAR(10) NOT NULL COMMENT "Challenge Rating (ex: 1/4, 1/2, 1, 2, etc.)",
    monster_cr_xp INT(6) NOT NULL COMMENT "XP correspondant au CR",
    monster_proficiency_bonus INT(1) NOT NULL,
    monster_passive_perception INT(2) NOT NULL,
    monster_languages TEXT NULL,
    monster_description TEXT NULL,
    monster_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monster_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (monster_id),
    UNIQUE KEY uk_monster_name_source (monster_name, source_id),
    KEY idx_source_id (source_id),
    KEY idx_size_id (size_id),
    KEY idx_type_id (type_id),
    KEY idx_alignment_id (alignment_id),
    KEY idx_monster_cr (monster_cr),
    KEY idx_monster_cr_xp (monster_cr_xp),
    CONSTRAINT fk_srd_monster_source FOREIGN KEY (source_id) REFERENCES content_source(source_id),
    CONSTRAINT fk_srd_monster_size FOREIGN KEY (size_id) REFERENCES creature_size(size_id),
    CONSTRAINT fk_srd_monster_type FOREIGN KEY (type_id) REFERENCES creature_type(type_id),
    CONSTRAINT fk_srd_monster_alignment FOREIGN KEY (alignment_id) REFERENCES alignment(alignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compétences des monstres
CREATE TABLE monster_skill (
    monster_skill_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    skill_id INT(11) NOT NULL,
    skill_bonus INT(2) NOT NULL,
    PRIMARY KEY (monster_skill_id),
    UNIQUE KEY uk_monster_skill (monster_id, skill_id),
    CONSTRAINT fk_monster_skill_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE,
    CONSTRAINT fk_monster_skill_skill FOREIGN KEY (skill_id) REFERENCES skill(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jets de sauvegarde des monstres
CREATE TABLE monster_saving_throw (
    save_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    ability_name VARCHAR(3) NOT NULL COMMENT "STR, DEX, CON, INT, WIS, CHA",
    save_bonus INT(2) NOT NULL,
    PRIMARY KEY (save_id),
    UNIQUE KEY uk_monster_save (monster_id, ability_name),
    CONSTRAINT fk_monster_save_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Résistances/immunités des monstres
CREATE TABLE monster_damage_resistance (
    resistance_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    damage_type_id INT(11) NOT NULL,
    resistance_type ENUM("resistance", "immunity", "vulnerability") NOT NULL,
    resistance_condition TEXT NULL COMMENT "Condition pour l'application",
    PRIMARY KEY (resistance_id),
    UNIQUE KEY uk_monster_resistance (monster_id, damage_type_id, resistance_type),
    CONSTRAINT fk_monster_resistance_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE,
    CONSTRAINT fk_monster_resistance_damage FOREIGN KEY (damage_type_id) REFERENCES damage_type(damage_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immunités aux conditions des monstres
CREATE TABLE monster_condition_immunity (
    immunity_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    condition_id INT(11) NOT NULL,
    PRIMARY KEY (immunity_id),
    UNIQUE KEY uk_monster_condition (monster_id, condition_id),
    CONSTRAINT fk_monster_immunity_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE,
    CONSTRAINT fk_monster_immunity_condition FOREIGN KEY (condition_id) REFERENCES condition_type(condition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sens des monstres
CREATE TABLE monster_sense (
    sense_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    sense_type VARCHAR(50) NOT NULL COMMENT "blindsight, darkvision, tremorsense, truesight",
    sense_range INT(3) NOT NULL COMMENT "Portée en pieds",
    sense_condition VARCHAR(100) NULL COMMENT "Condition d'application",
    PRIMARY KEY (sense_id),
    KEY idx_monster_id (monster_id),
    KEY idx_sense_type (sense_type),
    CONSTRAINT fk_monster_sense_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Traits des monstres
CREATE TABLE monster_trait (
    trait_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    trait_name VARCHAR(250) NOT NULL,
    trait_description TEXT NOT NULL,
    trait_type VARCHAR(50) NULL COMMENT "passive, legendary, lair",
    PRIMARY KEY (trait_id),
    KEY idx_monster_id (monster_id),
    KEY idx_trait_type (trait_type),
    CONSTRAINT fk_monster_trait_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actions des monstres
CREATE TABLE monster_action (
    action_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    action_name VARCHAR(250) NOT NULL,
    action_description TEXT NOT NULL,
    action_type ENUM("action", "legendary", "reaction", "bonus") NOT NULL DEFAULT "action",
    action_attack_bonus INT(2) NULL COMMENT "Bonus d'attaque",
    action_damage_dice VARCHAR(50) NULL COMMENT "Dégâts de l'action",
    action_reach_range VARCHAR(50) NULL COMMENT "Portée de l'action",
    action_usage VARCHAR(100) NULL COMMENT "Utilisation (recharge, 1/day, etc.)",
    PRIMARY KEY (action_id),
    KEY idx_monster_id (monster_id),
    KEY idx_action_type (action_type),
    CONSTRAINT fk_monster_action_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dégâts des actions de monstres
CREATE TABLE monster_action_damage (
    damage_id INT(11) NOT NULL AUTO_INCREMENT,
    action_id INT(11) NOT NULL,
    damage_type_id INT(11) NOT NULL,
    damage_dice VARCHAR(50) NOT NULL,
    damage_flat INT(3) NOT NULL DEFAULT 0,
    PRIMARY KEY (damage_id),
    KEY idx_action_id (action_id),
    KEY idx_damage_type (damage_type_id),
    CONSTRAINT fk_monster_action_damage_action FOREIGN KEY (action_id) REFERENCES monster_action(action_id) ON DELETE CASCADE,
    CONSTRAINT fk_monster_action_damage_type FOREIGN KEY (damage_type_id) REFERENCES damage_type(damage_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sorts des monstres
CREATE TABLE monster_spell (
    monster_spell_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_id INT(11) NOT NULL,
    spell_id INT(11) NOT NULL,
    spell_level INT(1) NOT NULL COMMENT "Niveau auquel le sort est lancé",
    spell_usage VARCHAR(100) NULL COMMENT "at_will, 1/day, etc.",
    PRIMARY KEY (monster_spell_id),
    UNIQUE KEY uk_monster_spell (monster_id, spell_id),
    CONSTRAINT fk_monster_spell_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id) ON DELETE CASCADE,
    CONSTRAINT fk_monster_spell_spell FOREIGN KEY (spell_id) REFERENCES srd_spell(spell_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backgrounds
CREATE TABLE srd_background (
    background_id INT(11) NOT NULL AUTO_INCREMENT,
    background_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    background_description TEXT NOT NULL,
    background_feature_name VARCHAR(250) NOT NULL,
    background_feature_description TEXT NOT NULL,
    background_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    background_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (background_id),
    UNIQUE KEY uk_background_name_source (background_name, source_id),
    KEY idx_source_id (source_id),
    CONSTRAINT fk_srd_background_source FOREIGN KEY (source_id) REFERENCES content_source(source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compétences de background
CREATE TABLE background_skill (
    background_skill_id INT(11) NOT NULL AUTO_INCREMENT,
    background_id INT(11) NOT NULL,
    skill_id INT(11) NOT NULL,
    PRIMARY KEY (background_skill_id),
    UNIQUE KEY uk_background_skill (background_id, skill_id),
    CONSTRAINT fk_background_skill_background FOREIGN KEY (background_id) REFERENCES srd_background(background_id) ON DELETE CASCADE,
    CONSTRAINT fk_background_skill_skill FOREIGN KEY (skill_id) REFERENCES skill(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Langues de background
CREATE TABLE background_language (
    background_language_id INT(11) NOT NULL AUTO_INCREMENT,
    background_id INT(11) NOT NULL,
    language_id INT(11) NULL,
    language_count INT(2) NULL COMMENT "Nombre de langues au choix si language_id est null",
    PRIMARY KEY (background_language_id),
    KEY idx_background_id (background_id),
    KEY idx_language_id (language_id),
    CONSTRAINT fk_background_language_background FOREIGN KEY (background_id) REFERENCES srd_background(background_id) ON DELETE CASCADE,
    CONSTRAINT fk_background_language_lang FOREIGN KEY (language_id) REFERENCES language(language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maîtrises d'outils de background
CREATE TABLE background_tool (
    tool_id INT(11) NOT NULL AUTO_INCREMENT,
    background_id INT(11) NOT NULL,
    tool_name VARCHAR(100) NOT NULL COMMENT "thieves tools, herbalism kit, etc.",
    tool_count INT(2) NULL COMMENT "Nombre d'outils au choix",
    PRIMARY KEY (tool_id),
    KEY idx_background_id (background_id),
    CONSTRAINT fk_background_tool_background FOREIGN KEY (background_id) REFERENCES srd_background(background_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Équipement de background
CREATE TABLE background_equipment (
    equipment_id INT(11) NOT NULL AUTO_INCREMENT,
    background_id INT(11) NOT NULL,
    equipment_description TEXT NOT NULL,
    equipment_gold_alternative VARCHAR(50) NULL COMMENT "Alternative en or",
    PRIMARY KEY (equipment_id),
    KEY idx_background_id (background_id),
    CONSTRAINT fk_background_equipment_background FOREIGN KEY (background_id) REFERENCES srd_background(background_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dons
CREATE TABLE srd_feat (
    feat_id INT(11) NOT NULL AUTO_INCREMENT,
    feat_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    feat_description TEXT NOT NULL,
    feat_prerequisite TEXT NULL,
    feat_ability_score_improvement BOOLEAN NOT NULL DEFAULT FALSE,
    feat_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    feat_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (feat_id),
    UNIQUE KEY uk_feat_name_source (feat_name, source_id),
    KEY idx_source_id (source_id),
    KEY idx_asi (feat_ability_score_improvement),
    CONSTRAINT fk_srd_feat_source FOREIGN KEY (source_id) REFERENCES content_source(source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificateurs de caractéristiques des dons
CREATE TABLE feat_ability_modifier (
    modifier_id INT(11) NOT NULL AUTO_INCREMENT,
    feat_id INT(11) NOT NULL,
    ability_name VARCHAR(3) NOT NULL,
    modifier_value INT(2) NOT NULL DEFAULT 1,
    is_optional BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (modifier_id),
    KEY idx_feat_id (feat_id),
    CONSTRAINT fk_feat_ability_feat FOREIGN KEY (feat_id) REFERENCES srd_feat(feat_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLES DE PERSONNAGES
-- ===================================

-- Personnages des joueurs
CREATE TABLE character (
    character_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    game_id INT(11) NULL COMMENT "NULL si personnage non assigné à une partie",
    character_name VARCHAR(250) NOT NULL,
    race_id INT(11) NOT NULL,
    subrace_id INT(11) NULL,
    background_id INT(11) NOT NULL,
    character_level INT(2) NOT NULL DEFAULT 1 COMMENT "Niveau total calculé",
    character_str INT(2) NOT NULL DEFAULT 10,
    character_dex INT(2) NOT NULL DEFAULT 10,
    character_con INT(2) NOT NULL DEFAULT 10,
    character_int INT(2) NOT NULL DEFAULT 10,
    character_wis INT(2) NOT NULL DEFAULT 10,
    character_cha INT(2) NOT NULL DEFAULT 10,
    character_hp_max INT(4) NOT NULL,
    character_hp_current INT(4) NOT NULL,
    character_hp_temp INT(3) NOT NULL DEFAULT 0,
    character_ac INT(2) NOT NULL,
    character_speed INT(2) NOT NULL DEFAULT 30,
    character_proficiency_bonus INT(1) NOT NULL DEFAULT 2,
    character_inspiration BOOLEAN NOT NULL DEFAULT FALSE,
    character_experience INT(7) NOT NULL DEFAULT 0,
    character_gold_cp INT(6) NOT NULL DEFAULT 0,
    character_gold_sp INT(6) NOT NULL DEFAULT 0,
    character_gold_ep INT(6) NOT NULL DEFAULT 0,
    character_gold_gp INT(6) NOT NULL DEFAULT 0,
    character_gold_pp INT(6) NOT NULL DEFAULT 0,
    character_personality_traits TEXT NULL,
    character_ideals TEXT NULL,
    character_bonds TEXT NULL,
    character_flaws TEXT NULL,
    character_backstory TEXT NULL,
    character_notes TEXT NULL,
    character_avatar VARCHAR(500) NULL,
    character_is_public BOOLEAN NOT NULL DEFAULT FALSE,
    character_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    character_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (character_id),
    KEY idx_user_id (user_id),
    KEY idx_game_id (game_id),
    KEY idx_race_id (race_id),
    KEY idx_subrace_id (subrace_id),
    KEY idx_background_id (background_id),
    KEY idx_character_level (character_level),
    KEY idx_character_public (character_is_public),
    CONSTRAINT fk_character_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE SET NULL,
    CONSTRAINT fk_character_race FOREIGN KEY (race_id) REFERENCES srd_race(race_id),
    CONSTRAINT fk_character_subrace FOREIGN KEY (subrace_id) REFERENCES srd_subrace(subrace_id),
    CONSTRAINT fk_character_background FOREIGN KEY (background_id) REFERENCES srd_background(background_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Niveaux multiclasse
CREATE TABLE character_class_level (
    class_level_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    class_id INT(11) NOT NULL,
    subclass_id INT(11) NULL,
    class_level INT(2) NOT NULL,
    is_primary_class BOOLEAN NOT NULL DEFAULT FALSE COMMENT "Première classe choisie",
    level_order INT(2) NOT NULL COMMENT "Ordre d'acquisition des niveaux (1, 2, 3...)",
    hit_points_gained INT(2) NOT NULL COMMENT "PV gagnés à ce niveau de classe",
    level_acquired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Quand ce niveau a été pris",
    PRIMARY KEY (class_level_id),
    UNIQUE KEY uk_character_class (character_id, class_id),
    UNIQUE KEY uk_character_primary_class (character_id, is_primary_class) WHERE is_primary_class = TRUE,
    UNIQUE KEY uk_character_level_order (character_id, level_order),
    KEY idx_class_id (class_id),
    KEY idx_is_primary_class (is_primary_class),
    KEY idx_level_order (level_order),
    CONSTRAINT fk_character_class_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_class_class FOREIGN KEY (class_id) REFERENCES srd_class(class_id),
    CONSTRAINT fk_character_class_subclass FOREIGN KEY (subclass_id) REFERENCES srd_subclass(subclass_id),
    CONSTRAINT chk_primary_class_level_order CHECK (is_primary_class = FALSE OR level_order = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compétences maîtrisées du personnage
CREATE TABLE character_skill (
    character_skill_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    skill_id INT(11) NOT NULL,
    proficiency_type ENUM("proficient", "expertise") NOT NULL DEFAULT "proficient",
    PRIMARY KEY (character_skill_id),
    UNIQUE KEY uk_character_skill (character_id, skill_id),
    CONSTRAINT fk_character_skill_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_skill_skill FOREIGN KEY (skill_id) REFERENCES skill(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jets de sauvegarde maîtrisés
CREATE TABLE character_saving_throw (
    save_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    ability_name VARCHAR(3) NOT NULL,
    is_proficient BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (save_id),
    UNIQUE KEY uk_character_save (character_id, ability_name),
    CONSTRAINT fk_character_save_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objets possédés par le personnage
CREATE TABLE character_item (
    character_item_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    item_id INT(11) NULL COMMENT "NULL pour objets non-SRD",
    item_name VARCHAR(250) NOT NULL COMMENT "Nom custom ou nom SRD",
    item_quantity INT(4) NOT NULL DEFAULT 1,
    item_description TEXT NULL COMMENT "Description custom",
    item_weight DECIMAL(5,2) NULL,
    item_value_gp DECIMAL(10,2) NULL,
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE,
    is_attuned BOOLEAN NOT NULL DEFAULT FALSE,
    item_notes TEXT NULL,
    PRIMARY KEY (character_item_id),
    KEY idx_character_id (character_id),
    KEY idx_item_id (item_id),
    KEY idx_equipped (is_equipped),
    KEY idx_attuned (is_attuned),
    CONSTRAINT fk_character_item_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_item_item FOREIGN KEY (item_id) REFERENCES srd_item(item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sorts connus/préparés du personnage
CREATE TABLE character_spell (
    character_spell_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    spell_id INT(11) NOT NULL,
    spell_source VARCHAR(50) NOT NULL COMMENT "class, racial, feat, item",
    is_known BOOLEAN NOT NULL DEFAULT TRUE,
    is_prepared BOOLEAN NOT NULL DEFAULT FALSE,
    is_always_prepared BOOLEAN NOT NULL DEFAULT FALSE,
    notes TEXT NULL,
    PRIMARY KEY (character_spell_id),
    UNIQUE KEY uk_character_spell_source (character_id, spell_id, spell_source),
    KEY idx_spell_id (spell_id),
    KEY idx_spell_source (spell_source),
    KEY idx_prepared (is_prepared),
    CONSTRAINT fk_character_spell_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_spell_spell FOREIGN KEY (spell_id) REFERENCES srd_spell(spell_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Emplacements de sorts actuels
CREATE TABLE character_spell_slot (
    slot_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    spell_level INT(1) NOT NULL COMMENT "1-9",
    slot_total INT(2) NOT NULL,
    slot_used INT(2) NOT NULL DEFAULT 0,
    PRIMARY KEY (slot_id),
    UNIQUE KEY uk_character_spell_level (character_id, spell_level),
    CONSTRAINT fk_character_slot_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Langues connues du personnage
CREATE TABLE character_language (
    character_language_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    language_id INT(11) NOT NULL,
    PRIMARY KEY (character_language_id),
    UNIQUE KEY uk_character_language (character_id, language_id),
    CONSTRAINT fk_character_language_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_language_lang FOREIGN KEY (language_id) REFERENCES language(language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maîtrises du personnage
CREATE TABLE character_proficiency (
    proficiency_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    proficiency_type VARCHAR(50) NOT NULL COMMENT "armor, weapon, tool",
    proficiency_value VARCHAR(100) NOT NULL,
    PRIMARY KEY (proficiency_id),
    KEY idx_character_id (character_id),
    KEY idx_proficiency_type (proficiency_type),
    CONSTRAINT fk_character_proficiency_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dons du personnage
CREATE TABLE character_feat (
    character_feat_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    feat_id INT(11) NOT NULL,
    level_obtained INT(2) NOT NULL,
    feat_choices VARCHAR(500) NULL COMMENT "Choix faits pour ce don",
    PRIMARY KEY (character_feat_id),
    UNIQUE KEY uk_character_feat (character_id, feat_id),
    KEY idx_feat_id (feat_id),
    KEY idx_level_obtained (level_obtained),
    CONSTRAINT fk_character_feat_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_feat_feat FOREIGN KEY (feat_id) REFERENCES srd_feat(feat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conditions actives du personnage
CREATE TABLE character_condition (
    character_condition_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    condition_id INT(11) NOT NULL,
    condition_level INT(2) NULL COMMENT "Pour exhaustion",
    duration_rounds INT(11) NULL,
    source_description VARCHAR(250) NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (character_condition_id),
    KEY idx_character_id (character_id),
    KEY idx_condition_id (condition_id),
    CONSTRAINT fk_character_condition_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_condition_condition FOREIGN KEY (condition_id) REFERENCES condition_type(condition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABLES DE COMBAT
-- ===================================

-- Table des combats
CREATE TABLE combat (
    combat_id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NOT NULL,
    map_id INT(11) NULL,
    combat_name VARCHAR(250) NOT NULL DEFAULT "Combat",
    combat_round INT(11) NOT NULL DEFAULT 1,
    combat_turn_order INT(11) NOT NULL DEFAULT 0,
    combat_status ENUM("preparation", "active", "paused", "finished") NOT NULL DEFAULT "preparation",
    combat_start DATETIME NULL,
    combat_end DATETIME NULL,
    combat_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (combat_id),
    KEY idx_game_id (game_id),
    KEY idx_map_id (map_id),
    KEY idx_combat_status (combat_status),
    CONSTRAINT fk_combat_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE CASCADE,
    CONSTRAINT fk_combat_map FOREIGN KEY (map_id) REFERENCES game_map(map_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participants au combat
CREATE TABLE combat_participant (
    participant_id INT(11) NOT NULL AUTO_INCREMENT,
    combat_id INT(11) NOT NULL,
    participant_type ENUM("character", "monster", "npc") NOT NULL,
    character_id INT(11) NULL,
    monster_id INT(11) NULL,
    token_id INT(11) NULL,
    participant_name VARCHAR(250) NOT NULL,
    participant_initiative INT(2) NOT NULL,
    participant_hp_current INT(11) NOT NULL,
    participant_hp_max INT(11) NOT NULL,
    participant_temp_hp INT(11) NOT NULL DEFAULT 0,
    participant_ac INT(2) NOT NULL,
    participant_has_acted BOOLEAN NOT NULL DEFAULT FALSE,
    participant_is_active BOOLEAN NOT NULL DEFAULT TRUE,
    participant_order INT(11) NOT NULL,
    PRIMARY KEY (participant_id),
    KEY idx_combat_id (combat_id),
    KEY idx_participant_type (participant_type),
    KEY idx_character_id (character_id),
    KEY idx_monster_id (monster_id),
    KEY idx_token_id (token_id),
    KEY idx_participant_initiative (participant_initiative),
    KEY idx_participant_order (participant_order),
    CONSTRAINT fk_combat_participant_combat FOREIGN KEY (combat_id) REFERENCES combat(combat_id) ON DELETE CASCADE,
    CONSTRAINT fk_combat_participant_character FOREIGN KEY (character_id) REFERENCES character(character_id),
    CONSTRAINT fk_combat_participant_monster FOREIGN KEY (monster_id) REFERENCES srd_monster(monster_id),
    CONSTRAINT fk_combat_participant_token FOREIGN KEY (token_id) REFERENCES game_token(token_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conditions des participants au combat
CREATE TABLE combat_participant_condition (
    condition_id INT(11) NOT NULL AUTO_INCREMENT,
    participant_id INT(11) NOT NULL,
    condition_type_id INT(11) NOT NULL,
    condition_level INT(2) NULL,
    duration_rounds INT(11) NULL,
    source_participant_id INT(11) NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (condition_id),
    KEY idx_participant_id (participant_id),
    KEY idx_condition_type (condition_type_id),
    KEY idx_source_participant (source_participant_id),
    CONSTRAINT fk_combat_condition_participant FOREIGN KEY (participant_id) REFERENCES combat_participant(participant_id) ON DELETE CASCADE,
    CONSTRAINT fk_combat_condition_type FOREIGN KEY (condition_type_id) REFERENCES condition_type(condition_id),
    CONSTRAINT fk_combat_condition_source FOREIGN KEY (source_participant_id) REFERENCES combat_participant(participant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actions de combat
CREATE TABLE combat_action (
    action_id INT(11) NOT NULL AUTO_INCREMENT,
    combat_id INT(11) NOT NULL,
    round_number INT(11) NOT NULL,
    actor_participant_id INT(11) NOT NULL,
    action_type VARCHAR(50) NOT NULL COMMENT "attack, spell, move, dash, etc.",
    target_participant_id INT(11) NULL,
    action_description TEXT NOT NULL,
    action_result JSON NULL COMMENT "Résultats détaillés de l'action",
    action_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (action_id),
    KEY idx_combat_id (combat_id),
    KEY idx_round_number (round_number),
    KEY idx_actor_participant (actor_participant_id),
    KEY idx_target_participant (target_participant_id),
    KEY idx_action_type (action_type),
    KEY idx_action_timestamp (action_timestamp),
    CONSTRAINT fk_combat_action_combat FOREIGN KEY (combat_id) REFERENCES combat(combat_id) ON DELETE CASCADE,
    CONSTRAINT fk_combat_action_actor FOREIGN KEY (actor_participant_id) REFERENCES combat_participant(participant_id),
    CONSTRAINT fk_combat_action_target FOREIGN KEY (target_participant_id) REFERENCES combat_participant(participant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- VUES POUR REQUÊTES COMPLEXES
-- ===================================

-- Vue pour les statistiques de personnage complètes
CREATE VIEW character_stats AS
SELECT 
    c.character_id,
    c.character_name,
    c.character_level,
    r.race_name,
    sr.subrace_name,
    cl.class_name,
    sc.subclass_name,
    b.background_name,
    c.character_str + COALESCE(ram_str.modifier_value, 0) + COALESCE(sam_str.modifier_value, 0) as str_total,
    c.character_dex + COALESCE(ram_dex.modifier_value, 0) + COALESCE(sam_dex.modifier_value, 0) as dex_total,
    c.character_con + COALESCE(ram_con.modifier_value, 0) + COALESCE(sam_con.modifier_value, 0) as con_total,
    c.character_int + COALESCE(ram_int.modifier_value, 0) + COALESCE(sam_int.modifier_value, 0) as int_total,
    c.character_wis + COALESCE(ram_wis.modifier_value, 0) + COALESCE(sam_wis.modifier_value, 0) as wis_total,
    c.character_cha + COALESCE(ram_cha.modifier_value, 0) + COALESCE(sam_cha.modifier_value, 0) as cha_total,
    c.character_hp_current,
    c.character_hp_max,
    c.character_ac,
    c.character_speed,
    c.character_proficiency_bonus
FROM character c
JOIN srd_race r ON c.race_id = r.race_id
LEFT JOIN srd_subrace sr ON c.subrace_id = sr.subrace_id
JOIN srd_class cl ON c.class_id = cl.class_id
LEFT JOIN srd_subclass sc ON c.subclass_id = sc.subclass_id
JOIN srd_background b ON c.background_id = b.background_id
LEFT JOIN race_ability_modifier ram_str ON c.race_id = ram_str.race_id AND ram_str.ability_name = "STR"
LEFT JOIN race_ability_modifier ram_dex ON c.race_id = ram_dex.race_id AND ram_dex.ability_name = "DEX"
LEFT JOIN race_ability_modifier ram_con ON c.race_id = ram_con.race_id AND ram_con.ability_name = "CON"
LEFT JOIN race_ability_modifier ram_int ON c.race_id = ram_int.race_id AND ram_int.ability_name = "INT"
LEFT JOIN race_ability_modifier ram_wis ON c.race_id = ram_wis.race_id AND ram_wis.ability_name = "WIS"
LEFT JOIN race_ability_modifier ram_cha ON c.race_id = ram_cha.race_id AND ram_cha.ability_name = "CHA"
LEFT JOIN subrace_ability_modifier sam_str ON c.subrace_id = sam_str.subrace_id AND sam_str.ability_name = "STR"
LEFT JOIN subrace_ability_modifier sam_dex ON c.subrace_id = sam_dex.subrace_id AND sam_dex.ability_name = "DEX"
LEFT JOIN subrace_ability_modifier sam_con ON c.subrace_id = sam_con.subrace_id AND sam_con.ability_name = "CON"
LEFT JOIN subrace_ability_modifier sam_int ON c.subrace_id = sam_int.subrace_id AND sam_int.ability_name = "INT"
LEFT JOIN subrace_ability_modifier sam_wis ON c.subrace_id = sam_wis.subrace_id AND sam_wis.ability_name = "WIS"
LEFT JOIN subrace_ability_modifier sam_cha ON c.subrace_id = sam_cha.subrace_id AND sam_cha.ability_name = "CHA";

-- Vue pour les sorts disponibles par classe et niveau
CREATE VIEW spell_by_class_level AS
SELECT 
    s.spell_id,
    s.spell_name,
    s.spell_level,
    sc.school_name,
    s.spell_concentration,
    s.spell_ritual,
    cl.class_name,
    cl.class_id,
    cs.source_abbreviation
FROM srd_spell s
JOIN spell_school sc ON s.school_id = sc.school_id
JOIN spell_class spc ON s.spell_id = spc.spell_id
JOIN srd_class cl ON spc.class_id = cl.class_id
JOIN content_source cs ON s.source_id = cs.source_id
ORDER BY cl.class_name, s.spell_level, s.spell_name;

-- Vue pour les monstres avec statistiques complètes
CREATE VIEW monster_complete AS
SELECT 
    m.monster_id,
    m.monster_name,
    cs.size_name,
    ct.type_name,
    a.alignment_name,
    m.monster_ac,
    m.monster_hp_average,
    m.monster_cr,
    m.monster_cr_xp,
    COUNT(DISTINCT mt.trait_id) as trait_count,
    COUNT(DISTINCT ma.action_id) as action_count,
    COUNT(DISTINCT ms.sense_id) as sense_count,
    cs.source_abbreviation
FROM srd_monster m
JOIN creature_size cs ON m.size_id = cs.size_id
JOIN creature_type ct ON m.type_id = ct.type_id
LEFT JOIN alignment a ON m.alignment_id = a.alignment_id
LEFT JOIN monster_trait mt ON m.monster_id = mt.monster_id
LEFT JOIN monster_action ma ON m.monster_id = ma.monster_id
LEFT JOIN monster_sense ms ON m.monster_id = ms.monster_id
JOIN content_source cs ON m.source_id = cs.source_id
GROUP BY m.monster_id;

-- Vue pour retrouver l'interface simple (compatibilité)
CREATE VIEW character_summary AS
SELECT 
    c.*,
    ccl_primary.class_id as primary_class_id,
    ccl_primary.subclass_id as primary_subclass_id,
    sc.class_name as primary_class_name,
    GROUP_CONCAT(
        CONCAT(sc2.class_name, ' ', ccl2.class_level) 
        ORDER BY ccl2.level_order 
        SEPARATOR ' / '
    ) as class_progression
FROM character c
JOIN character_class_level ccl_primary ON c.character_id = ccl_primary.character_id 
    AND ccl_primary.is_primary_class = TRUE
JOIN srd_class sc ON ccl_primary.class_id = sc.class_id
LEFT JOIN character_class_level ccl2 ON c.character_id = ccl2.character_id
LEFT JOIN srd_class sc2 ON ccl2.class_id = sc2.class_id
GROUP BY c.character_id;

-- Vue pour les statistiques de classes
CREATE VIEW character_class_stats AS
SELECT 
    character_id,
    class_id,
    class_level,
    is_primary_class,
    level_order,
    CASE 
        WHEN is_primary_class THEN 'Primary'
        ELSE 'Multiclass'
    END as class_role
FROM character_class_level
ORDER BY character_id, level_order;

-- ===================================
-- DONNÉES DE RÉFÉRENCE INITIALES
-- ===================================

-- Sources de contenu principales
INSERT INTO content_source (source_abbreviation, source_full_name, source_type, source_is_official) VALUES
("PHB", "Player's Handbook", "core", TRUE),
("DMG", "Dungeon Master's Guide", "core", TRUE),
("MM", "Monster Manual", "core", TRUE),
("SCAG", "Sword Coast Adventurer's Guide", "supplement", TRUE),
("VGM", "Volo's Guide to Monsters", "supplement", TRUE),
("XGE", "Xanathar's Guide to Everything", "supplement", TRUE),
("TCoE", "Tasha's Cauldron of Everything", "supplement", TRUE),
("FTD", "Fizban's Treasury of Dragons", "supplement", TRUE),
("MotM", "Monsters of the Multiverse", "supplement", TRUE),
("SRD", "System Reference Document", "core", TRUE);

-- Types de dégâts
INSERT INTO damage_type (damage_type_name, damage_type_category) VALUES
("slashing", "physical"),
("piercing", "physical"),
("bludgeoning", "physical"),
("acid", "elemental"),
("cold", "elemental"),
("fire", "elemental"),
("lightning", "elemental"),
("thunder", "elemental"),
("force", "magical"),
("necrotic", "magical"),
("radiant", "magical"),
("psychic", "magical"),
("poison", "special");

-- Écoles de magie
INSERT INTO spell_school (school_name, school_description, school_color) VALUES
("Abjuration", "Magic that blocks, banishes, or protects", "#4F46E5"),
("Conjuration", "Magic that brings creatures or materials to the caster", "#059669"),
("Divination", "Magic that reveals information", "#7C3AED"),
("Enchantment", "Magic that entrances and beguiles", "#DC2626"),
("Evocation", "Magic that creates powerful elemental effects", "#EA580C"),
("Illusion", "Magic that dazzles the senses", "#6366F1"),
("Necromancy", "Magic that manipulates the forces of life and death", "#1F2937"),
("Transmutation", "Magic that transforms creatures and objects", "#0891B2");

-- Tailles de créatures
INSERT INTO creature_size (size_name, size_abbreviation, size_space_feet, size_space_squares) VALUES
("Tiny", "T", 2, 1),
("Small", "S", 5, 1),
("Medium", "M", 5, 1),
("Large", "L", 10, 4),
("Huge", "H", 15, 9),
("Gargantuan", "G", 20, 16);

-- Types de créatures
INSERT INTO creature_type (type_name, type_description) VALUES
("aberration", "Utterly alien beings"),
("beast", "Nonhumanoid creatures that are part of the natural world"),
("celestial", "Creatures native to the Upper Planes"),
("construct", "Made, not born"),
("dragon", "Large reptilian creatures of ancient origin"),
("elemental", "Creatures native to the elemental planes"),
("fey", "Magical creatures closely tied to nature"),
("fiend", "Creatures native to the Lower Planes"),
("giant", "Towering humanoid creatures"),
("humanoid", "The main peoples of the world"),
("monstrosity", "Frightening creatures not ordinary or natural"),
("ooze", "Gelatinous creatures that rarely have fixed shapes"),
("plant", "Vegetable creatures"),
("undead", "Once-living creatures brought to undeath");

-- Alignements
INSERT INTO alignment (alignment_name, alignment_abbreviation, alignment_law_chaos, alignment_good_evil) VALUES
("lawful good", "LG", "lawful", "good"),
("neutral good", "NG", "neutral", "good"),
("chaotic good", "CG", "chaotic", "good"),
("lawful neutral", "LN", "lawful", "neutral"),
("neutral", "N", "neutral", "neutral"),
("chaotic neutral", "CN", "chaotic", "neutral"),
("lawful evil", "LE", "lawful", "evil"),
("neutral evil", "NE", "neutral", "evil"),
("chaotic evil", "CE", "chaotic", "evil"),
("unaligned", "U", "neutral", "neutral");

-- Conditions
INSERT INTO condition_type (condition_name, condition_description) VALUES
("blinded", "A blinded creature can't see and automatically fails any ability check that requires sight."),
("charmed", "A charmed creature can't attack the charmer or target the charmer with harmful abilities or magical effects."),
("deafened", "A deafened creature can't hear and automatically fails any ability check that requires hearing."),
("exhaustion", "Some special abilities and environmental hazards, such as starvation and the long-term effects of freezing or scorching temperatures, can lead to a special condition called exhaustion."),
("frightened", "A frightened creature has disadvantage on ability checks and attack rolls while the source of its fear is within line of sight."),
("grappled", "A grappled creature's speed becomes 0, and it can't benefit from any bonus to its speed."),
("incapacitated", "An incapacitated creature can't take actions or reactions."),
("invisible", "An invisible creature is impossible to see without the aid of magic or a special sense."),
("paralyzed", "A paralyzed creature is incapacitated and can't move or speak."),
("petrified", "A petrified creature is transformed, along with any nonmagical object it is wearing or carrying, into a solid inanimate substance."),
("poisoned", "A poisoned creature has disadvantage on attack rolls and ability checks."),
("prone", "A prone creature's only movement option is to crawl, unless it stands up and thereby ends the condition."),
("restrained", "A restrained creature's speed becomes 0, and it can't benefit from any bonus to its speed."),
("stunned", "A stunned creature is incapacitated, can't move, and can speak only falteringly."),
("unconscious", "An unconscious creature is incapacitated, can't move or speak, and is unaware of its surroundings.");

-- Compétences
INSERT INTO skill (skill_name, skill_ability, skill_description) VALUES
("Acrobatics", "DEX", "Your Dexterity (Acrobatics) check covers your attempt to stay on your feet in a tricky situation"),
("Animal Handling", "WIS", "When there is any question whether you can calm down a domesticated animal, keep a mount from getting spooked, or intuit an animal's intentions"),
("Arcana", "INT", "Your Intelligence (Arcana) check measures your ability to recall lore about spells, magic items, eldritch symbols, magical traditions, the planes of existence, and the inhabitants of those planes"),
("Athletics", "STR", "Your Strength (Athletics) check covers difficult situations you encounter while climbing, jumping, or swimming"),
("Deception", "CHA", "Your Charisma (Deception) check determines whether you can convincingly hide the truth"),
("History", "INT", "Your Intelligence (History) check measures your ability to recall lore about historical events, legendary people, ancient kingdoms, past disputes, recent wars, and lost civilizations"),
("Insight", "WIS", "Your Wisdom (Insight) check decides whether you can determine the true intentions of a creature"),
("Intimidation", "CHA", "When you attempt to influence someone through overt threats, hostile actions, and physical violence"),
("Investigation", "INT", "When you look around for clues and make deductions based on those clues"),
("Medicine", "WIS", "A Wisdom (Medicine) check lets you try to stabilize a dying companion or diagnose an illness"),
("Nature", "INT", "Your Intelligence (Nature) check measures your ability to recall lore about terrain, plants and animals, the weather, and natural cycles"),
("Perception", "WIS", "Your Wisdom (Perception) check lets you spot, hear, or otherwise detect the presence of something"),
("Performance", "CHA", "Your Charisma (Performance) check determines how well you can delight an audience with music, dance, acting, storytelling, or some other form of entertainment"),
("Persuasion", "CHA", "When you attempt to influence someone or a group of people with tact, social graces, or good nature"),
("Religion", "INT", "Your Intelligence (Religion) check measures your ability to recall lore about deities, rites and prayers, religious hierarchies, holy symbols, and the practices of secret cults"),
("Sleight of Hand", "DEX", "Whenever you attempt an act of legerdemain or manual trickery"),
("Stealth", "DEX", "Make a Dexterity (Stealth) check when you attempt to conceal yourself from enemies"),
("Survival", "WIS", "The GM might ask you to make a Wisdom (Survival) check to follow tracks, hunt wild game, guide your group through frozen wastelands, identify signs that owlbears live nearby, predict the weather, or avoid quicksand and other natural hazards");

-- Langues
INSERT INTO language (language_name, language_type, language_script) VALUES
("Common", "standard", "Common"),
("Dwarvish", "standard", "Dwarvish"),
("Elvish", "standard", "Elvish"),
("Giant", "standard", "Dwarvish"),
("Gnomish", "standard", "Dwarvish"),
("Goblin", "standard", "Dwarvish"),
("Halfling", "standard", "Common"),
("Orc", "standard", "Dwarvish"),
("Abyssal", "exotic", "Infernal"),
("Celestial", "exotic", "Celestial"),
("Draconic", "exotic", "Draconic"),
("Deep Speech", "exotic", NULL),
("Infernal", "exotic", "Infernal"),
("Primordial", "exotic", "Dwarvish"),
("Sylvan", "exotic", "Elvish"),
("Undercommon", "exotic", "Elvish");

-- Raretés d"objets
INSERT INTO item_rarity (rarity_name, rarity_color, rarity_order) VALUES
("common", "#6B7280", 0),
("uncommon", "#10B981", 1),
("rare", "#3B82F6", 2),
("very rare", "#8B5CF6", 3),
("legendary", "#F59E0B", 4),
("artifact", "#EF4444", 5);

-- Catégories d"objets
INSERT INTO item_category (category_name, category_abbreviation, category_description) VALUES
("Weapon", "W", "Items used to make attacks"),
("Armor", "A", "Items worn to improve Armor Class"),
("Shield", "SH", "Items held to improve Armor Class"),
("Adventuring Gear", "G", "Mundane equipment for adventures"),
("Tools", "T", "Items used for specific tasks"),
("Wondrous Item", "WI", "Miscellaneous magical items"),
("Potion", "P", "Consumable magical items"),
("Ring", "R", "Magical jewelry worn on fingers"),
("Rod", "RD", "Magical implements"),
("Staff", "ST", "Magical implements with charges"),
("Wand", "WN", "Magical implements with limited uses"),
("Scroll", "SC", "Spell scrolls and documents");

-- Propriétés d"armes
INSERT INTO weapon_property (property_name, property_abbreviation, property_description) VALUES
("Ammunition", "A", "You can use a weapon that has the ammunition property to make a ranged attack only if you have ammunition to fire from the weapon."),
("Finesse", "F", "When making an attack with a finesse weapon, you use your choice of your Strength or Dexterity modifier for the attack and damage rolls."),
("Heavy", "H", "Small creatures have disadvantage on attack rolls with heavy weapons."),
("Light", "L", "A light weapon is small and easy to handle, making it ideal for use when fighting with two weapons."),
("Loading", "LD", "Because of the time required to load this weapon, you can fire only one piece of ammunition from it when you use an action, bonus action, or reaction to fire it."),
("Range", "R", "A weapon that can be used to make a ranged attack has a range shown in parentheses after the ammunition or thrown property."),
("Reach", "RE", "This weapon adds 5 feet to your reach when you attack with it."),
("Special", "S", "A weapon with the special property has unusual rules governing its use."),
("Thrown", "T", "If a weapon has the thrown property, you can throw the weapon to make a ranged attack."),
("Two-Handed", "2H", "This weapon requires two hands to use."),
("Versatile", "V", "This weapon can be used with one or two hands.");

-- ===================================
-- TRIGGERS POUR MISE À JOUR AUTOMATIQUE
-- ===================================

-- Trigger pour mettre à jour automatiquement character.character_updated_at
DELIMITER //
CREATE TRIGGER tr_character_updated_at 
    BEFORE UPDATE ON character 
    FOR EACH ROW 
BEGIN
    SET NEW.character_updated_at = CURRENT_TIMESTAMP;
END//

-- Trigger pour calculer automatiquement les modificateurs de caractéristiques
CREATE TRIGGER tr_character_ability_check
    BEFORE INSERT ON character
    FOR EACH ROW
BEGIN
    -- Vérification que les scores de caractéristiques sont dans les limites (3-20)
    IF NEW.character_str < 3 OR NEW.character_str > 20 THEN
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Strength must be between 3 and 20";
    END IF;
    IF NEW.character_dex < 3 OR NEW.character_dex > 20 THEN
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Dexterity must be between 3 and 20";
    END IF;
    IF NEW.character_con < 3 OR NEW.character_con > 20 THEN
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Constitution must be between 3 and 20";
    END IF;
    IF NEW.character_int < 3 OR NEW.character_int > 20 THEN
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Intelligence must be between 3 and 20";
    END IF;
    IF NEW.character_wis < 3 OR NEW.character_wis > 20 THEN
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Wisdom must be between 3 and 20";
    END IF;
    IF NEW.character_cha < 3 OR NEW.character_cha > 20 THEN
        SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Charisma must be between 3 and 20";
    END IF;
END//

-- Trigger pour vérifier la cohérence des PV
CREATE TRIGGER tr_character_hp_check
    BEFORE UPDATE ON character
    FOR EACH ROW
BEGIN
    IF NEW.character_hp_current < 0 THEN
        SET NEW.character_hp_current = 0;
    END IF;
    IF NEW.character_hp_current > NEW.character_hp_max + NEW.character_hp_temp THEN
        SET NEW.character_hp_current = NEW.character_hp_max + NEW.character_hp_temp;
    END IF;
END//

-- Trigger pour mettre à jour l"ordre des participants au combat
CREATE TRIGGER tr_combat_participant_order
    BEFORE INSERT ON combat_participant
    FOR EACH ROW
BEGIN
    IF NEW.participant_order = 0 THEN
        SET NEW.participant_order = (
            SELECT COALESCE(MAX(participant_order), 0) + 1 
            FROM combat_participant 
            WHERE combat_id = NEW.combat_id
        );
    END IF;
END//

-- Trigger pour nettoyer les conditions expirées
CREATE TRIGGER tr_cleanup_expired_conditions
    BEFORE UPDATE ON combat
    FOR EACH ROW
BEGIN
    -- Supprimer les conditions expirées quand le round change
    IF NEW.combat_round > OLD.combat_round THEN
        DELETE FROM combat_participant_condition 
        WHERE participant_id IN (
            SELECT participant_id 
            FROM combat_participant 
            WHERE combat_id = NEW.combat_id
        )
        AND duration_rounds IS NOT NULL 
        AND duration_rounds <= 0;
        
        -- Décrémenter la durée des conditions restantes
        UPDATE combat_participant_condition 
        SET duration_rounds = duration_rounds - 1
        WHERE participant_id IN (
            SELECT participant_id 
            FROM combat_participant 
            WHERE combat_id = NEW.combat_id
        )
        AND duration_rounds IS NOT NULL 
        AND duration_rounds > 0;
    END IF;
END//

DELIMITER ;

DELIMITER //
CREATE TRIGGER tr_update_character_level_on_class_insert
    AFTER INSERT ON character_class_level
    FOR EACH ROW
BEGIN
    UPDATE character 
    SET character_level = (
        SELECT SUM(class_level) 
        FROM character_class_level 
        WHERE character_id = NEW.character_id
    )
    WHERE character_id = NEW.character_id;
END//

CREATE TRIGGER tr_update_character_level_on_class_update
    AFTER UPDATE ON character_class_level
    FOR EACH ROW
BEGIN
    UPDATE character 
    SET character_level = (
        SELECT SUM(class_level) 
        FROM character_class_level 
        WHERE character_id = NEW.character_id
    )
    WHERE character_id = NEW.character_id;
END//

CREATE TRIGGER tr_update_character_level_on_class_delete
    AFTER DELETE ON character_class_level
    FOR EACH ROW
BEGIN
    UPDATE character 
    SET character_level = COALESCE((
        SELECT SUM(class_level) 
        FROM character_class_level 
        WHERE character_id = OLD.character_id
    ), 1)
    WHERE character_id = OLD.character_id;
END//

CREATE TRIGGER tr_validate_character_has_primary_class
    BEFORE INSERT ON character_class_level
    FOR EACH ROW
BEGIN
    -- Si c'est le premier niveau (level_order = 1), il doit être primary
    IF NEW.level_order = 1 AND NEW.is_primary_class = FALSE THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'First class level must be primary class';
    END IF;
    
    -- Si ce n'est pas le premier niveau, il ne peut pas être primary
    IF NEW.level_order > 1 AND NEW.is_primary_class = TRUE THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only first class can be primary class';
    END IF;
END//

DELIMITER ;

-- ===================================
-- PROCÉDURES STOCKÉES UTILITAIRES
-- ===================================

DELIMITER //

-- Procédure pour calculer l"XP nécessaire pour un niveau donné
CREATE PROCEDURE GetXPForLevel(IN level INT, OUT xp_required INT)
BEGIN
    CASE level
        WHEN 1 THEN SET xp_required = 0;
        WHEN 2 THEN SET xp_required = 300;
        WHEN 3 THEN SET xp_required = 900;
        WHEN 4 THEN SET xp_required = 2700;
        WHEN 5 THEN SET xp_required = 6500;
        WHEN 6 THEN SET xp_required = 14000;
        WHEN 7 THEN SET xp_required = 23000;
        WHEN 8 THEN SET xp_required = 34000;
        WHEN 9 THEN SET xp_required = 48000;
        WHEN 10 THEN SET xp_required = 64000;
        WHEN 11 THEN SET xp_required = 85000;
        WHEN 12 THEN SET xp_required = 100000;
        WHEN 13 THEN SET xp_required = 120000;
        WHEN 14 THEN SET xp_required = 140000;
        WHEN 15 THEN SET xp_required = 165000;
        WHEN 16 THEN SET xp_required = 195000;
        WHEN 17 THEN SET xp_required = 225000;
        WHEN 18 THEN SET xp_required = 265000;
        WHEN 19 THEN SET xp_required = 305000;
        WHEN 20 THEN SET xp_required = 355000;
        ELSE SET xp_required = 355000;
    END CASE;
END//

-- Procédure pour calculer le bonus de maîtrise selon le niveau
CREATE PROCEDURE GetProficiencyBonus(IN level INT, OUT bonus INT)
BEGIN
    CASE 
        WHEN level >= 17 THEN SET bonus = 6;
        WHEN level >= 13 THEN SET bonus = 5;
        WHEN level >= 9 THEN SET bonus = 4;
        WHEN level >= 5 THEN SET bonus = 3;
        ELSE SET bonus = 2;
    END CASE;
END//

-- Procédure pour calculer le modificateur d"une caractéristique
CREATE FUNCTION GetAbilityModifier(ability_score INT) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN FLOOR((ability_score - 10) / 2);
END//

-- Procédure pour lancer les dés
CREATE PROCEDURE RollDice(
    IN user_id INT,
    IN game_id INT,
    IN character_id INT,
    IN dice_expression VARCHAR(500),
    IN roll_type VARCHAR(50),
    IN roll_context VARCHAR(250),
    IN is_private BOOLEAN
)
BEGIN
    DECLARE dice_total INT;
    DECLARE dice_result JSON;
    
    -- Ici on simule le lancer (dans la vraie implémentation, 
    -- cela serait fait par le service PHP)
    SET dice_total = FLOOR(1 + RAND() * 20);
    SET dice_result = JSON_OBJECT(
        "expression", dice_expression,
        "rolls", JSON_ARRAY(dice_total),
        "total", dice_total
    );
    
    INSERT INTO dice_roll (
        user_id, game_id, character_id, roll_expression, 
        roll_result, roll_total, roll_type, roll_context, roll_is_private
    ) VALUES (
        user_id, game_id, character_id, dice_expression,
        dice_result, dice_total, roll_type, roll_context, is_private
    );
    
    SELECT LAST_INSERT_ID() as roll_id, dice_total as total, dice_result as result;
END//

-- Procédure pour démarrer un combat
CREATE PROCEDURE StartCombat(IN game_id INT, IN map_id INT, IN combat_name VARCHAR(250))
BEGIN
    DECLARE combat_id INT;
    
    INSERT INTO combat (game_id, map_id, combat_name, combat_status)
    VALUES (game_id, map_id, combat_name, "preparation");
    
    SET combat_id = LAST_INSERT_ID();
    
    SELECT combat_id;
END//

-- Procédure pour ajouter un participant au combat
CREATE PROCEDURE AddCombatParticipant(
    IN p_combat_id INT,
    IN p_participant_type ENUM("character", "monster", "npc"),
    IN p_entity_id INT,
    IN p_name VARCHAR(250),
    IN p_initiative INT,
    IN p_hp_max INT,
    IN p_ac INT
)
BEGIN
    DECLARE p_order INT;
    
    -- Calculer l"ordre basé sur l"initiative
    SELECT COUNT(*) + 1 INTO p_order
    FROM combat_participant 
    WHERE combat_id = p_combat_id 
    AND participant_initiative >= p_initiative;
    
    -- Décaler les participants avec initiative plus faible
    UPDATE combat_participant 
    SET participant_order = participant_order + 1
    WHERE combat_id = p_combat_id 
    AND participant_initiative < p_initiative;
    
    INSERT INTO combat_participant (
        combat_id, participant_type, participant_name,
        participant_initiative, participant_hp_current, participant_hp_max,
        participant_ac, participant_order
    ) VALUES (
        p_combat_id, p_participant_type, p_name,
        p_initiative, p_hp_max, p_hp_max, p_ac, p_order
    );
    
    -- Assigner l"ID spécifique selon le type
    IF p_participant_type = "character" THEN
        UPDATE combat_participant 
        SET character_id = p_entity_id 
        WHERE participant_id = LAST_INSERT_ID();
    ELSEIF p_participant_type = "monster" THEN
        UPDATE combat_participant 
        SET monster_id = p_entity_id 
        WHERE participant_id = LAST_INSERT_ID();
    END IF;
    
    SELECT LAST_INSERT_ID() as participant_id;
END//

DELIMITER ;

-- ===================================
-- INDEXES SUPPLÉMENTAIRES POUR PERFORMANCE
-- ===================================

-- Indexes composés pour les requêtes fréquentes
CREATE INDEX idx_character_game_level ON character(game_id, character_level);
CREATE INDEX idx_character_user_game ON character(user_id, game_id);
CREATE INDEX idx_spell_level_school ON srd_spell(spell_level, school_id);
CREATE INDEX idx_monster_cr_type ON srd_monster(monster_cr, type_id);
CREATE INDEX idx_combat_game_status ON combat(game_id, combat_status);
CREATE INDEX idx_message_game_type_created ON game_message(game_id, message_type, message_created_at);
CREATE INDEX idx_dice_roll_game_created ON dice_roll(game_id, roll_created_at);

-- Index full-text pour la recherche
CREATE FULLTEXT INDEX ft_spell_search ON srd_spell(spell_name, spell_description);
CREATE FULLTEXT INDEX ft_monster_search ON srd_monster(monster_name, monster_description);
CREATE FULLTEXT INDEX ft_item_search ON srd_item(item_name, item_description);

-- ===================================
-- CONFIGURATION ET OPTIMISATIONS
-- ===================================

-- Configuration MySQL pour les performances
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL query_cache_size = 268435456; -- 256MB
SET GLOBAL query_cache_type = ON;
SET GLOBAL slow_query_log = ON;
SET GLOBAL long_query_time = 2;

-- ===================================
-- VUES SUPPLÉMENTAIRES POUR L"API
-- ===================================

-- Vue pour les personnages avec informations complètes
CREATE VIEW character_full_info AS
SELECT 
    c.*,
    r.race_name,
    sr.subrace_name,
    cl.class_name,
    sc.subclass_name,
    b.background_name,
    u.user_username,
    g.game_name,
    (c.character_str + COALESCE(ram_str.modifier_value, 0) + COALESCE(sam_str.modifier_value, 0)) as str_total,
    (c.character_dex + COALESCE(ram_dex.modifier_value, 0) + COALESCE(sam_dex.modifier_value, 0)) as dex_total,
    (c.character_con + COALESCE(ram_con.modifier_value, 0) + COALESCE(sam_con.modifier_value, 0)) as con_total,
    (c.character_int + COALESCE(ram_int.modifier_value, 0) + COALESCE(sam_int.modifier_value, 0)) as int_total,
    (c.character_wis + COALESCE(ram_wis.modifier_value, 0) + COALESCE(sam_wis.modifier_value, 0)) as wis_total,
    (c.character_cha + COALESCE(ram_cha.modifier_value, 0) + COALESCE(sam_cha.modifier_value, 0)) as cha_total
FROM character c
JOIN user u ON c.user_id = u.user_id
LEFT JOIN game g ON c.game_id = g.game_id
JOIN srd_race r ON c.race_id = r.race_id
LEFT JOIN srd_subrace sr ON c.subrace_id = sr.subrace_id
JOIN srd_class cl ON c.class_id = cl.class_id
LEFT JOIN srd_subclass sc ON c.subclass_id = sc.subclass_id
JOIN srd_background b ON c.background_id = b.background_id
LEFT JOIN race_ability_modifier ram_str ON c.race_id = ram_str.race_id AND ram_str.ability_name = "STR"
LEFT JOIN race_ability_modifier ram_dex ON c.race_id = ram_dex.race_id AND ram_dex.ability_name = "DEX"
LEFT JOIN race_ability_modifier ram_con ON c.race_id = ram_con.race_id AND ram_con.ability_name = "CON"
LEFT JOIN race_ability_modifier ram_int ON c.race_id = ram_int.race_id AND ram_int.ability_name = "INT"
LEFT JOIN race_ability_modifier ram_wis ON c.race_id = ram_wis.race_id AND ram_wis.ability_name = "WIS"
LEFT JOIN race_ability_modifier ram_cha ON c.race_id = ram_cha.race_id AND ram_cha.ability_name = "CHA"
LEFT JOIN subrace_ability_modifier sam_str ON c.subrace_id = sam_str.subrace_id AND sam_str.ability_name = "STR"
LEFT JOIN subrace_ability_modifier sam_dex ON c.subrace_id = sam_dex.subrace_id AND sam_dex.ability_name = "DEX"
LEFT JOIN subrace_ability_modifier sam_con ON c.subrace_id = sam_con.subrace_id AND sam_con.ability_name = "CON"
LEFT JOIN subrace_ability_modifier sam_int ON c.subrace_id = sam_int.subrace_id AND sam_int.ability_name = "INT"
LEFT JOIN subrace_ability_modifier sam_wis ON c.subrace_id = sam_wis.subrace_id AND sam_wis.ability_name = "WIS"
LEFT JOIN subrace_ability_modifier sam_cha ON c.subrace_id = sam_cha.subrace_id AND sam_cha.ability_name = "CHA";

-- Vue pour les parties avec informations des joueurs
CREATE VIEW game_with_players AS
SELECT 
    g.*,
    u.user_username as gm_username,
    COUNT(gp.game_player_id) as player_count,
    GROUP_CONCAT(up.user_username SEPARATOR ", ") as player_names
FROM game g
JOIN user u ON g.game_master_id = u.user_id
LEFT JOIN game_player gp ON g.game_id = gp.game_id AND gp.player_status = "active"
LEFT JOIN user up ON gp.user_id = up.user_id
GROUP BY g.game_id;

-- Vue pour les combats actifs avec participants
CREATE VIEW active_combat_summary AS
SELECT 
    c.combat_id,
    c.game_id,
    c.combat_name,
    c.combat_round,
    c.combat_status,
    COUNT(cp.participant_id) as participant_count,
    cp_current.participant_name as current_participant,
    cp_current.participant_initiative as current_initiative
FROM combat c
LEFT JOIN combat_participant cp ON c.combat_id = cp.combat_id AND cp.participant_is_active = TRUE
LEFT JOIN combat_participant cp_current ON c.combat_id = cp_current.combat_id 
    AND cp_current.participant_order = c.combat_turn_order
WHERE c.combat_status IN ("preparation", "active", "paused")
GROUP BY c.combat_id;

-- ===================================
-- DONNÉES DE TEST (OPTIONNEL)
-- ===================================

-- Utilisateur de test (mot de passe hashé pour "password123")
INSERT INTO user (user_username, user_email, user_password, user_roles, user_is_verified) VALUES
("testgm", "testgm@example.com", "$2y$13$5v8k1F2mQ9x7N3pL6hR8ZeK4W2sD1fG3nM8oP5tV9yU7iQ2eR6wS0a", "["ROLE_USER", "ROLE_GM"]", TRUE),
("testplayer1", "player1@example.com", "$2y$13$5v8k1F2mQ9x7N3pL6hR8ZeK4W2sD1fG3nM8oP5tV9yU7iQ2eR6wS0a", "["ROLE_USER"]", TRUE),
("testplayer2", "player2@example.com", "$2y$13$5v8k1F2mQ9x7N3pL6hR8ZeK4W2sD1fG3nM8oP5tV9yU7iQ2eR6wS0a", "["ROLE_USER"]", TRUE);

-- Partie de test
INSERT INTO game (game_name, game_description, game_master_id, game_status, game_settings) VALUES
("Campagne de Test", "Une campagne pour tester les fonctionnalités", 1, "active", 
"{"house_rules": {"critical_fumbles": true, "death_saves": true}, "xp_mode": "milestone"}");

-- Ajout des joueurs à la partie
INSERT INTO game_player (game_id, user_id, player_role, player_status) VALUES
(1, 2, "player", "active"),
(1, 3, "player", "active");

-- ===================================
-- FINALISATION
-- ===================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;