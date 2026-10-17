<?php

namespace App\Domain\Cms\Legacy;

/** Descriptive titles / alt text for the photographs on the old duncanbd.com (keyed by their legacy file name). */
class LegacyAltText
{
    private const MAP = [
        '1486439110_nursery_1.jpg' => 'Tea seedlings in a nursery',
        '1486442537_nursery_2.jpg' => 'Young tea plants in the nursery',
        '1486444602_section_with_new_shade_sets.jpg' => 'A tea section planted with new shade trees',
        '1486444944_plucking_1.jpg' => 'Plucking tea leaves by hand',
        '1486445013_plucking_2.jpg' => 'Pluckers working along the tea rows',
        '1486445125_plucking_3.jpg' => 'Tea plucking in the estate',
        '1486449130_tea_garden.jpg' => 'A tea garden in Bangladesh',
        '1486449199_factory.jpg' => 'Inside a tea factory',
        '1486449232_karimpore_factory.jpg' => 'Karimpore tea factory',
        '1486437415_rubber_garden.jpg' => 'A rubber garden',
        '1486437447_tapping_(female_worker).jpg' => 'A worker tapping a rubber tree',
        '1486437496_tapping_(male_worker).jpg' => 'Tapping rubber latex',
        '1486437525_rubber_collection.jpg' => 'Collecting rubber latex',
        '1486437574_milling_machine.jpg' => 'Rubber milling machine',
        '1486437614_rss_sheet_rolled_in_milling_machine.jpg' => 'Rubber sheet rolled in the milling machine',
        '1486437638_rss_sheet_after_cogulation.jpg' => 'Rubber sheet after coagulation',
        '1486438781_rss_sheet_ready_to_be_smoked.jpg' => 'Ribbed sheets ready to be smoked',
        '1486438478_raw_materials.jpg' => 'Raw rubber materials',
        '1486437967_rss.jpg' => 'Ribbed smoked sheet rubber',
        '1486390267_dpl_factory.jpg' => 'The Duncan Products factory',
        '1486390292_dpl_labeling.jpg' => 'Bottle labelling at Duncan Products',
        '1486390316_dpl_quality_test.jpg' => 'Quality testing of mineral water',
        '1486390358_duncans_water.jpg' => "Duncan's natural mineral water",
        '1486390484_orchid_(pink).jpg' => 'Pink orchids',
        '1486390594_orchid_(purple).jpg' => 'Purple orchids',
        '1486390804_orchid_(yellow).jpg' => 'Yellow orchids',
        '1486391126_orchid_garden.jpg' => 'The orchid garden',
        '1486449341_cdf_school.jpg' => 'The Camellia Duncan Foundation school',
        '1486449381_cdfs_students.jpg' => 'Students at the Camellia Duncan Foundation school',
        '1486449410_cdfs_playground.jpg' => 'The school playground',
        '1486449433_cdf_hospital.jpg' => 'The Camellia Duncan Foundation hospital',
        '1486449469_diagnostics.jpg' => 'Diagnostics at the hospital',
        '1486449503_cdfh_ot.jpg' => 'Hospital operating theatre',
        '1486449570_cdf_hospital_ward.jpg' => 'A hospital ward',
        '1486449595_cdfh_ward.jpg' => 'Patient ward',
        '1486449615_clinic.jpg' => 'Community clinic',
        'cwl.jpg' => 'Certificate awarded to Chittagong Warehouses Ltd.',
        'db_vat.jpg' => 'VAT award certificate',
    ];

    public static function for(string $file): ?string
    {
        return self::MAP[$file] ?? null;
    }
}
