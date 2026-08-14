CREATE TABLE `goalapp_kims_test_goalapp`.`clauses_category` (
  `category_id` int(11) NOT NULL,
  `category_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `goalapp_kims_test_goalapp`.`clauses_category` ADD PRIMARY KEY (`category_id`);
ALTER TABLE `goalapp_kims_test_goalapp`.`clauses_category` MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1 ;

CREATE TABLE `goalapp_kims_test_goalapp`.`clauses_list` (
  `custom_field_id` int(11) NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_default_value` text COLLATE utf8mb4_unicode_ci,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `contract_type` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rental Agreement',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `order_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `goalapp_kims_test_goalapp`.`clauses_list` ADD PRIMARY KEY (`custom_field_id`);
ALTER TABLE `goalapp_kims_test_goalapp`.`clauses_list` MODIFY `custom_field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1 ;

CREATE TABLE `goalapp_kims_test_goalapp`.`contract_templates` (
  `id` int(11) NOT NULL,
  `contract_type` int(11) DEFAULT NULL,
  `template_content` text,
  `is_required` int(1) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `goalapp_kims_test_goalapp`.`contract_templates` ADD PRIMARY KEY (`id`);
ALTER TABLE `goalapp_kims_test_goalapp`.`contract_templates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1 ;
