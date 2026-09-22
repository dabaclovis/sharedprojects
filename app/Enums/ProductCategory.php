<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Electronics = 'Electronics';
    case Audio = 'Audio';
    case Computers = 'Computers';
    case HomeAndKitchen = 'Home & Kitchen';
    case Fashion = 'Fashion';
    case Beauty = 'Beauty';
    case HealthAndWellness = 'Health & Wellness';
    case SportsAndOutdoors = 'Sports & Outdoors';
    case Books = 'Books';
    case ToysAndGames = 'Toys & Games';
    case Software = 'Software';
    case Other = 'Other';
    case Automotive = 'Automotive';
    case GardenAndOutdoor = 'Garden & Outdoor';
    case Pets = 'Pets';
    case OfficeSupplies = 'Office Supplies';
    case IndustrialAndScientific = 'Industrial & Scientific';
    case MusicalInstruments = 'Musical Instruments';
    case VideoGames = 'Video Games';
    case Jewelry = 'Jewelry';
    case BeautyAndPersonalCare = 'Beauty & Personal Care';
    case Furniture = 'Furniture';
    case ToolsAndHomeImprovement = 'Tools & Home Improvement';
    case Appliances = 'Appliances';

    public function label(): string
    {
        return $this->value;
    }
}
