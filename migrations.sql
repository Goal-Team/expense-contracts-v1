-- 1) agreement_templates
CREATE TABLE IF NOT EXISTS agreement_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contract_type BIGINT UNSIGNED NULL,
    payment_type VARCHAR(255) NULL,
    entity_type_id BIGINT UNSIGNED NULL,
    template_name VARCHAR(255) NULL,
    template_html LONGTEXT NULL,
    source_docx_path VARCHAR(255) NULL,
    source_docx_filename VARCHAR(255) NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'draft',
    version_no INT UNSIGNED NOT NULL DEFAULT 1,
    published_scope_key VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY agreement_templates_published_scope_key_unique (published_scope_key),
    KEY agreement_templates_scope_idx (contract_type, payment_type, entity_type_id),
    KEY agreement_templates_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) agreement_template_variables
CREATE TABLE IF NOT EXISTS agreement_template_variables (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    agreement_template_id BIGINT UNSIGNED NOT NULL,
    variable_key VARCHAR(255) NOT NULL,
    source VARCHAR(255) NULL,
    required TINYINT(1) NOT NULL DEFAULT 0,
    default_value TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY agreement_template_vars_idx (agreement_template_id, variable_key),
    CONSTRAINT agreement_template_variables_agreement_template_id_foreign
        FOREIGN KEY (agreement_template_id)
        REFERENCES agreement_templates (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) agreement_template_renders
CREATE TABLE IF NOT EXISTS agreement_template_renders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    agreement_template_id BIGINT UNSIGNED NOT NULL,
    merge_input_json LONGTEXT NULL,
    rendered_docx_path VARCHAR(255) NULL,
    rendered_pdf_path VARCHAR(255) NULL,
    render_status VARCHAR(255) NOT NULL DEFAULT 'draft',
    generated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY agreement_template_renders_idx (agreement_template_id),
    CONSTRAINT agreement_template_renders_agreement_template_id_foreign
        FOREIGN KEY (agreement_template_id)
        REFERENCES agreement_templates (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;