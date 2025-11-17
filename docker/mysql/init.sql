-- Script d'initialisation MySQL (optionnel)
-- Exécuté au premier démarrage

-- Créer des utilisateurs supplémentaires si nécessaire
-- CREATE USER IF NOT EXISTS 'readonly'@'%' IDENTIFIED BY 'readonly_pass';
-- GRANT SELECT ON onlyroll.* TO 'readonly'@'%';

-- Configurer les performances
SET GLOBAL max_connections = 200;

-- Log
SELECT 'MySQL initialization completed' AS status;
