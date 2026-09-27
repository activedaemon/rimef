-- noinspection SqlNoDataSourceInspectionForFile
-- noinspection SqlResolveForFile
-- =============================================================================
-- RIMeF - Droits MySQL pour les bases de données tenant
-- =============================================================================
-- stancl/tenancy crée dynamiquement une base par tenant, préfixée `rimef_tenant_`
-- (ex. tenant `rimef` → base `rimef_tenant_rimef`).
-- L'utilisateur applicatif `rimef` doit pouvoir CREATE/DROP/USE ces bases.
--
-- Ce script est exécuté UNIQUEMENT à la première création du volume MySQL
-- (mécanisme /docker-entrypoint-initdb.d/ de l'image officielle mysql).
-- Pour le ré-appliquer : `make down` puis `make start` (destructif).
-- =============================================================================

GRANT ALL PRIVILEGES ON `rimef\_tenant\_%`.* TO 'rimef'@'%';
FLUSH PRIVILEGES;
