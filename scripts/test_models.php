<?php
// test_models.php - Database cleanup eklenmiş versiyon

// Laravel'i başlat
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Language;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Table;
use App\Models\QRCode;
use App\Models\QRCodeScan;
use App\Models\Invoice;
use App\Models\InvoiceItem;

use Illuminate\Support\Facades\DB;

try {
    echo "🧹 Cleaning up database...\n";
    
    // Foreign key constraint'leri geçici olarak kapat
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // Test verilerini sil (tersi sırayla - foreign key'ler için)
    UserSubscription::truncate();
    echo "   ✅ UserSubscriptions cleared\n";
    
    Restaurant::truncate();
    echo "   ✅ Restaurants cleared\n";
    
    SubscriptionPlan::truncate();
    echo "   ✅ SubscriptionPlans cleared\n";

    Branch::truncate();
    echo "   ✅ Branches cleared\n";

    InvoiceItem::truncate();
    echo "   ✅ InvoiceItems cleared\n";

    Invoice::truncate();
    echo "   ✅ Invoices cleared\n";

    QRCodeScan::truncate();
    echo "   ✅ QRCodeScans cleared\n";

    QRCode::truncate();
    echo "   ✅ QRCodes cleared\n";

    // Cleanup kısmına ekle (MenuItem'dan önce)
    Table::truncate();
    echo "   ✅ Tables cleared\n";

    MenuItem::truncate();
    echo "   ✅ MenuItems cleared\n";

    MenuCategory::truncate();
    echo "   ✅ MenuCategories cleared\n";
    
    
    
    

    Language::truncate();
    echo "   ✅ Languages cleared\n";
    
    // User tablosunu dikkatli temizle (Laravel auth user'ları bozmasın)
    User::where('email', 'LIKE', '%test%')->delete();
    User::where('email', 'LIKE', '%@restaurant.com')->delete();
    echo "   ✅ Test Users cleared\n";
    
    // Media dosyalarını temizle (Spatie Media Library)
    DB::table('media')->truncate();
    echo "   ✅ Media files cleared\n";
    
    // Activity log temizle
    DB::table('activity_log')->truncate();
    echo "   ✅ Activity logs cleared\n";

    
    
    // Foreign key constraint'leri tekrar aç
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "🧹 Database cleanup completed!\n\n";

    // ================================
    // Test verilerini oluştur
    // ================================
    
    echo "🚀 Creating test data...\n\n";

    // 1. Language test
    $language = Language::create([
        'code' => 'en',
        'name' => 'English', 
        'native_name' => 'English',
        'flag_icon' => '🇺🇸',
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0
    ]);

    echo "✅ Language created: ID = " . $language->id . "\n";

    // 2. SubscriptionPlan test
    $plan = SubscriptionPlan::create([
        'slug' => 'standard',
        'name' => ['en' => 'Standard Plan', 'tr' => 'Standart Plan'],
        'description' => ['en' => 'Perfect for small restaurants', 'tr' => 'Küçük restoranlar için ideal'],
        'price_usd' => 29.99,
        'price_try' => 899.99,
        'price_eur' => 24.99,
        'billing_period' => 'monthly',
        'max_restaurants' => 3,
        'max_branches' => 5,
        'max_menu_items' => 100,
        'max_users' => 5,
        'max_qr_scans_monthly' => 1000,
        'features' => ['multi_language', 'analytics', 'qr_codes', 'media_upload'],
        'is_active' => true,
        'sort_order' => 1
    ]);

    echo "✅ SubscriptionPlan created: ID = " . $plan->id . "\n";

    // 3. User test
    $user = User::create([
        'name' => 'Test Restaurant Owner',
        'email' => 'test@restaurant.com',
        'password' => bcrypt('password123'),
        'phone' => '+1234567890',
        'country' => 'US',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    echo "✅ User created: ID = " . $user->id . "\n";

    // 4. UserSubscription test
    $userSubscription = UserSubscription::create([
        'user_id' => $user->id,
        'subscription_plan_id' => $plan->id,
        'gateway' => 'stripe',
        'gateway_subscription_id' => 'sub_test123',
        'status' => 'active',
        'amount' => 29.99,
        'currency' => 'USD',
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
        'current_restaurants' => 1,
        'current_branches' => 2,
        'current_menu_items' => 10,
        'current_users' => 1,
        'monthly_qr_scans' => 25,
    ]);

    echo "✅ UserSubscription created: ID = " . $userSubscription->id . "\n";

    // 5. Restaurant test
    $restaurant = Restaurant::create([
        'slug' => 'pizza-palace',
        'name' => [
            'en' => 'Pizza Palace',
            'tr' => 'Pizza Sarayı'
        ],
        'description' => [
            'en' => 'Authentic Italian pizza in the heart of the city',
            'tr' => 'Şehrin kalbinde otantik İtalyan pizzası'
        ],
        'email' => 'info@pizzapalace.com',
        'phone' => '+1-555-0123',
        'address' => [
            'en' => '123 Main Street, Downtown',
            'tr' => 'Ana Cadde 123, Merkez'
        ],
        'website' => 'https://pizzapalace.com',
        'country' => 'US',
        'city' => 'New York',
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'cuisine_type' => 'italian',
        'average_price' => 25.00,
        'primary_color' => '#FF6B35',
        'secondary_color' => '#F7931E',
        'currency' => 'USD',
        'timezone' => 'America/New_York',
        'business_hours' => [
            'monday' => ['is_open' => true, 'open' => '11:00', 'close' => '22:00'],
            'tuesday' => ['is_open' => true, 'open' => '11:00', 'close' => '22:00'],
            'wednesday' => ['is_open' => true, 'open' => '11:00', 'close' => '22:00'],
            'thursday' => ['is_open' => true, 'open' => '11:00', 'close' => '22:00'],
            'friday' => ['is_open' => true, 'open' => '11:00', 'close' => '23:00'],
            'saturday' => ['is_open' => true, 'open' => '12:00', 'close' => '23:00'],
            'sunday' => ['is_open' => true, 'open' => '12:00', 'close' => '21:00']
        ],
        'is_active' => true,
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    echo "✅ Restaurant created: ID = " . $restaurant->id . "\n";
    echo "   Name: " . $restaurant->localized_name . "\n";
    echo "   Slug: " . $restaurant->slug . "\n";
    echo "   Address: " . $restaurant->full_address . "\n";
    echo "   Formatted Price: " . $restaurant->formatted_price . "\n";
    echo "   Is Open: " . ($restaurant->isOpen() ? 'Yes' : 'No') . "\n\n";

    // 6. Relationships test
    echo "🔗 Testing Relationships:\n";
    echo "   User -> Subscription Plan: " . $userSubscription->subscriptionPlan->localized_name . "\n";
    echo "   Subscription -> User: " . $userSubscription->user->name . "\n";
    echo "   Restaurant Details: " . $restaurant->localized_description . "\n\n";

    echo "🎉 All models working correctly!\n";
    echo "📊 Test Data Summary:\n";
    echo "   Languages: " . Language::count() . "\n";
    echo "   Plans: " . SubscriptionPlan::count() . "\n";
    echo "   Users: " . User::count() . "\n";
    echo "   Subscriptions: " . UserSubscription::count() . "\n";
    echo "   Restaurants: " . Restaurant::count() . "\n";

    // 6. Branch test
$branch = Branch::create([
    'restaurant_id' => $restaurant->id,
    'name' => [
        'en' => 'Downtown Branch',
        'tr' => 'Merkez Şube'
    ],
    'slug' => 'downtown',
    'description' => [
        'en' => 'Main branch in the city center',
        'tr' => 'Şehir merkezindeki ana şube'
    ],
    'phone' => '+1-555-0124',
    'address' => [
        'en' => '456 Broadway Street',
        'tr' => 'Broadway Caddesi 456'
    ],
    'city' => 'New York',
    'district' => 'Manhattan',
    'postal_code' => '10001',
    'latitude' => 40.7589,
    'longitude' => -73.9851,
    'table_count' => 20,
    'capacity' => 80,
    'manager_id' => $user->id,
    'business_hours' => [
        'monday' => ['is_open' => true, 'open' => '10:00', 'close' => '23:00'],
        'tuesday' => ['is_open' => true, 'open' => '10:00', 'close' => '23:00'],
        'wednesday' => ['is_open' => true, 'open' => '10:00', 'close' => '23:00'],
        'thursday' => ['is_open' => true, 'open' => '10:00', 'close' => '23:00'],
        'friday' => ['is_open' => true, 'open' => '10:00', 'close' => '24:00'],
        'saturday' => ['is_open' => true, 'open' => '10:00', 'close' => '24:00'],
        'sunday' => ['is_open' => true, 'open' => '11:00', 'close' => '22:00']
    ],
    'is_active' => true,
    'accepts_orders' => true,
    'opening_date' => now()->subMonths(6),
]);

echo "✅ Branch created: ID = " . $branch->id . "\n";
echo "   Name: " . $branch->localized_name . "\n";
echo "   Restaurant: " . $branch->restaurant->localized_name . "\n";
echo "   Full Address: " . $branch->full_address . "\n";
echo "   Manager: " . $branch->manager->name . "\n";
echo "   Is Open: " . ($branch->isOpen() ? 'Yes' : 'No') . "\n";
echo "   Can Accept Orders: " . ($branch->canAcceptOrders() ? 'Yes' : 'No') . "\n";
echo "   Table Count: " . $branch->table_count . "\n";
echo "   Capacity: " . $branch->capacity . "\n\n";

// 7. MenuCategory test (Branch'tan sonra)
$menuCategory = MenuCategory::create([
    'restaurant_id' => $restaurant->id,
    'branch_id' => $branch->id,
    'name' => [
        'en' => 'Pizzas',
        'tr' => 'Pizzalar'
    ],
    'slug' => 'pizzas',
    'description' => [
        'en' => 'Authentic Italian pizzas with fresh ingredients',
        'tr' => 'Taze malzemelerle hazırlanan otantik İtalyan pizzaları'
    ],
    'icon' => '🍕',
    'color' => '#FF6B35',
    'sort_order' => 1,
    'is_active' => true,
    'available_times' => [
        'start' => '11:00',
        'end' => '23:00'
    ],
    'available_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
    'meta_title' => [
        'en' => 'Delicious Pizzas - Pizza Palace',
        'tr' => 'Lezzetli Pizzalar - Pizza Sarayı'
    ]
]);

echo "✅ MenuCategory created: ID = " . $menuCategory->id . "\n";
echo "   Name: " . $menuCategory->localized_name . "\n";
echo "   Restaurant: " . $menuCategory->restaurant->localized_name . "\n";
echo "   Branch: " . $menuCategory->branch->localized_name . "\n";
echo "   Full Name: " . $menuCategory->full_name . "\n";
echo "   Icon: " . $menuCategory->icon_display . "\n";
echo "   Is Available Now: " . ($menuCategory->isAvailableNow() ? 'Yes' : 'No') . "\n";
echo "   Has Sub Categories: " . ($menuCategory->hasSubCategories() ? 'Yes' : 'No') . "\n";
echo "   Is Sub Category: " . ($menuCategory->isSubCategory() ? 'Yes' : 'No') . "\n\n";

// Sub-category test
$subCategory = MenuCategory::create([
    'restaurant_id' => $restaurant->id,
    'branch_id' => $branch->id,
    'parent_id' => $menuCategory->id,
    'name' => [
        'en' => 'Vegetarian Pizzas',
        'tr' => 'Vejetaryen Pizzalar'
    ],
    'slug' => 'vegetarian-pizzas',
    'description' => [
        'en' => 'Delicious meat-free pizza options',
        'tr' => 'Lezzetli etsiz pizza seçenekleri'
    ],
    'icon' => '🥬',
    'color' => '#4CAF50',
    'sort_order' => 1,
    'is_active' => true,
]);

echo "✅ Sub-Category created: ID = " . $subCategory->id . "\n";
echo "   Name: " . $subCategory->localized_name . "\n";
echo "   Full Name: " . $subCategory->full_name . "\n";
echo "   Parent: " . $subCategory->parent->localized_name . "\n";
echo "   Depth: " . $subCategory->getDepth() . "\n\n";

$menuItem = MenuItem::create([
    'restaurant_id' => $restaurant->id,
    'branch_id' => $branch->id,
    'menu_category_id' => $menuCategory->id,
    'name' => [
        'en' => 'Margherita Pizza',
        'tr' => 'Margherita Pizza'
    ],
    'slug' => 'margherita-pizza',
    'description' => [
        'en' => 'Classic pizza with tomato sauce, mozzarella, and fresh basil',
        'tr' => 'Domates sosu, mozzarella ve taze fesleğen ile klasik pizza'
    ],
    'ingredients' => [
        'en' => 'Tomato sauce, mozzarella cheese, fresh basil, olive oil',
        'tr' => 'Domates sosu, mozzarella peyniri, taze fesleğen, zeytinyağı'
    ],
    'price' => 18.99,
    'cost' => 7.50,
    'discount_price' => 15.99,
    'discount_starts_at' => now(),
    'discount_ends_at' => now()->addDays(7),
    'calories' => 280,
    'protein' => 12.5,
    'carbs' => 35.0,
    'fat' => 9.5,
    'prep_time' => 15,
    'dietary_tags' => ['vegetarian'],
    'allergens' => ['gluten', 'dairy'],
    'spice_level' => 'none',
    'sizes' => [
        'small' => 15.99,
        'medium' => 18.99,
        'large' => 22.99
    ],
    'extras' => [
        'extra_cheese' => 2.50,
        'mushrooms' => 2.00,
        'olives' => 1.50
    ],
    'is_available' => true,
    'is_featured' => true,
    'stock_quantity' => 50,
    'available_times' => [
        'start' => '11:00',
        'end' => '23:00'
    ],
    'available_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
    'sort_order' => 1,
    'view_count' => 125,
    'order_count' => 34,
    'rating' => 4.7,
    'rating_count' => 28,
]);

echo "✅ MenuItem created: ID = " . $menuItem->id . "\n";
echo "   Name: " . $menuItem->localized_name . "\n";
echo "   Category: " . $menuItem->menuCategory->localized_name . "\n";
echo "   Restaurant: " . $menuItem->restaurant->localized_name . "\n";
echo "   Branch: " . $menuItem->branch->localized_name . "\n";
echo "   Current Price: " . $menuItem->current_price . "\n";
echo "   Formatted Price: " . strip_tags($menuItem->formatted_price) . "\n";
echo "   Discount: " . ($menuItem->discount_percentage ?? 0) . "%\n";
echo "   Is On Sale: " . ($menuItem->isOnSale() ? 'Yes' : 'No') . "\n";
echo "   Is Available Now: " . ($menuItem->isAvailableNow() ? 'Yes' : 'No') . "\n";
echo "   Is In Stock: " . ($menuItem->isInStock() ? 'Yes' : 'No') . "\n";
echo "   Is Vegetarian: " . ($menuItem->isVegetarian() ? 'Yes' : 'No') . "\n";
echo "   Is Vegan: " . ($menuItem->isVegan() ? 'Yes' : 'No') . "\n";
echo "   Profit Margin: " . ($menuItem->profit_margin ?? 0) . "%\n";
echo "   Rating: " . $menuItem->rating . "/5 (" . $menuItem->rating_count . " reviews)\n";
echo "   Prep Time: " . $menuItem->prep_time . " minutes\n\n";

// Second menu item (different category)
$menuItem2 = MenuItem::create([
    'restaurant_id' => $restaurant->id,
    'branch_id' => $branch->id,
    'menu_category_id' => $subCategory->id, // Vegetarian category
    'name' => [
        'en' => 'Veggie Supreme Pizza',
        'tr' => 'Vejetaryen Süprem Pizza'
    ],
    'slug' => 'veggie-supreme-pizza',
    'description' => [
        'en' => 'Loaded with fresh vegetables and cheese',
        'tr' => 'Taze sebzeler ve peynir ile dolu'
    ],
    'ingredients' => [
        'en' => 'Tomato sauce, mozzarella, bell peppers, mushrooms, onions, olives',
        'tr' => 'Domates sosu, mozzarella, biber, mantar, soğan, zeytin'
    ],
    'price' => 21.99,
    'cost' => 8.75,
    'calories' => 320,
    'protein' => 14.0,
    'carbs' => 38.0,
    'fat' => 11.0,
    'prep_time' => 18,
    'dietary_tags' => ['vegetarian'],
    'allergens' => ['gluten', 'dairy'],
    'spice_level' => 'mild',
    'is_available' => true,
    'is_featured' => false,
    'stock_quantity' => 30,
    'sort_order' => 1,
    'rating' => 4.5,
    'rating_count' => 15,
]);

echo "✅ Second MenuItem created: ID = " . $menuItem2->id . "\n";
echo "   Name: " . $menuItem2->localized_name . "\n";
echo "   Category: " . $menuItem2->menuCategory->localized_name . "\n";
echo "   Parent Category: " . $menuItem2->menuCategory->parent->localized_name . "\n";
echo "   Price: " . $menuItem2->formatted_price . "\n";
echo "   Profit Margin: " . ($menuItem2->profit_margin ?? 0) . "%\n\n";

// 9. Table test (MenuItem'dan sonra)
$table1 = Table::create([
    'restaurant_id' => $restaurant->id,
    'branch_id' => $branch->id,
    'number' => 'T01',
    'name' => 'Window Table 1',
    'description' => [
        'en' => 'Cozy table by the window with city view',
        'tr' => 'Şehir manzaralı pencere kenarında rahat masa'
    ],
    'capacity' => 4,
    'shape' => 'round',
    'location' => 'window',
    'position' => ['x' => 100, 'y' => 150],
    'features' => ['window_view', 'power_outlet', 'wheelchair_accessible'],
    'is_smoking_allowed' => false,
    'is_outdoor' => false,
    'is_private' => false,
    'is_active' => true,
    'status' => 'available',
    'accepts_reservations' => true,
    'min_reservation_duration' => 60,
    'max_reservation_duration' => 180,
    'service_charge' => 0.00,
    'minimum_order' => 25.00,
]);

echo "✅ Table 1 created: ID = " . $table1->id . "\n";
echo "   Display Name: " . $table1->display_name . "\n";
echo "   Restaurant: " . $table1->restaurant->localized_name . "\n";
echo "   Branch: " . $table1->branch->localized_name . "\n";
echo "   Capacity: " . $table1->capacity . " people\n";
echo "   Status: " . $table1->status . " " . $table1->status_badge . "\n";
echo "   Is Available: " . ($table1->isAvailable() ? 'Yes' : 'No') . "\n";
echo "   Can Be Reserved: " . ($table1->canBeReserved() ? 'Yes' : 'No') . "\n";
echo "   Has Window View: " . ($table1->hasWindowView() ? 'Yes' : 'No') . "\n";
echo "   Is Wheelchair Accessible: " . ($table1->isWheelchairAccessible() ? 'Yes' : 'No') . "\n";
echo "   Minimum Order: " . $table1->formatted_minimum_order . "\n";
echo "   Position: x=" . $table1->position_coordinates['x'] . ", y=" . $table1->position_coordinates['y'] . "\n\n";

// Table 2 - Different status
$table2 = Table::create([
    'restaurant_id' => $restaurant->id,
    'branch_id' => $branch->id,
    'number' => 'T02',
    'name' => 'Outdoor Patio Table',
    'description' => [
        'en' => 'Lovely outdoor table on the patio',
        'tr' => 'Terasta güzel açık hava masası'
    ],
    'capacity' => 6,
    'shape' => 'rectangle',
    'location' => 'patio',
    'position' => ['x' => 250, 'y' => 300],
    'features' => ['outdoor', 'umbrella', 'heater'],
    'is_smoking_allowed' => true,
    'is_outdoor' => true,
    'is_private' => false,
    'is_active' => true,
    'status' => 'occupied',
    'accepts_reservations' => true,
    'min_reservation_duration' => 90,
    'max_reservation_duration' => 240,
    'service_charge' => 5.00,
]);

echo "✅ Table 2 created: ID = " . $table2->id . "\n";
echo "   Display Name: " . $table2->display_name . "\n";
echo "   Capacity: " . $table2->capacity . " people\n";
echo "   Status: " . $table2->status . " " . $table2->status_badge . "\n";
echo "   Is Occupied: " . ($table2->isOccupied() ? 'Yes' : 'No') . "\n";
echo "   Is Outdoor: " . ($table2->is_outdoor ? 'Yes' : 'No') . "\n";
echo "   Smoking Allowed: " . ($table2->is_smoking_allowed ? 'Yes' : 'No') . "\n";
echo "   Service Charge: " . $table2->formatted_service_charge . "\n\n";

// Test table capacity check
echo "🧪 Testing Capacity Checks:\n";
echo "   Table 1 can accommodate 3 people: " . ($table1->canAccommodate(3) ? 'Yes' : 'No') . "\n";
echo "   Table 1 can accommodate 5 people: " . ($table1->canAccommodate(5) ? 'Yes' : 'No') . "\n";
echo "   Table 2 can accommodate 6 people: " . ($table2->canAccommodate(6) ? 'Yes' : 'No') . "\n\n";

// Test status changes
echo "🔄 Testing Status Changes:\n";
$table1->markAsReserved();
echo "   Table 1 marked as reserved: " . $table1->fresh()->status . "\n";
$table1->markAsAvailable();
echo "   Table 1 marked as available: " . $table1->fresh()->status . "\n\n";

$restaurantQR = QRCode::create([
    'code' => QRCode::generateUniqueCode('rest_'),
    'qrcodeable_type' => Restaurant::class,
    'qrcodeable_id' => $restaurant->id,
    'type' => 'restaurant',
    'url' => 'https://qrmenu.test/restaurant/' . $restaurant->slug,
    'design_options' => [
        'size' => 300,
        'foreground_color' => '#FF6B35',
        'background_color' => '#FFFFFF',
        'error_correction' => 'M',
    ],
    'format' => 'png',
    'size' => 300,
    'is_active' => true,
]);

echo "✅ Restaurant QR created: ID = " . $restaurantQR->id . "\n";
echo "   Code: " . $restaurantQR->code . "\n";
echo "   Type: " . $restaurantQR->type_display . "\n";
echo "   Status: " . $restaurantQR->status_badge . "\n";
echo "   Is Active: " . ($restaurantQR->isActive() ? 'Yes' : 'No') . "\n";
echo "   Linked to: " . $restaurantQR->qrcodeable->localized_name . "\n\n";

// Table QR Code  
$tableQR = QRCode::create([
    'code' => QRCode::generateUniqueCode('table_'),
    'qrcodeable_type' => Table::class,
    'qrcodeable_id' => $table1->id,
    'type' => 'table',
    'url' => 'https://qrmenu.test/table/' . $table1->number,
    'format' => 'png',
    'size' => 250,
    'scan_count' => 15,
    'last_scanned_at' => now()->subHours(2),
    'is_active' => true,
]);

echo "✅ Table QR created: ID = " . $tableQR->id . "\n";
echo "   Code: " . $tableQR->code . "\n";
echo "   Type: " . $tableQR->type_display . "\n";
echo "   Linked to: Table " . $tableQR->qrcodeable->display_name . "\n";
echo "   Scan Count: " . $tableQR->scan_count . "\n";
echo "   Last Scanned: " . $tableQR->last_scanned_at->diffForHumans() . "\n\n";

// Menu Item QR Code
$itemQR = QRCode::create([
    'code' => QRCode::generateUniqueCode('item_'),
    'qrcodeable_type' => MenuItem::class,
    'qrcodeable_id' => $menuItem->id,
    'type' => 'item',
    'url' => 'https://qrmenu.test/item/' . $menuItem->slug,
    'format' => 'svg',
    'size' => 200,
    'scan_count' => 3,
    'is_active' => true,
    'max_scans' => 100,
]);

echo "✅ MenuItem QR created: ID = " . $itemQR->id . "\n";
echo "   Code: " . $itemQR->code . "\n";
echo "   Type: " . $itemQR->type_display . "\n";
echo "   Linked to: " . $itemQR->qrcodeable->localized_name . "\n";
echo "   Format: " . $itemQR->format . "\n";
echo "   Max Scans: " . $itemQR->max_scans . "\n";
echo "   Remaining Scans: " . ($itemQR->max_scans - $itemQR->scan_count) . "\n";
echo "   Is Limit Reached: " . ($itemQR->isLimitReached() ? 'Yes' : 'No') . "\n\n";

// Test polymorphic relationships (Analytics olmadan)
echo "🔗 Testing Polymorphic Relationships:\n";
echo "   Restaurant has " . $restaurant->qrCodes()->count() . " QR codes\n";
echo "   Table 1 has " . $table1->qrCodes()->count() . " QR codes\n";
echo "   Menu Item has " . $menuItem->qrCodes()->count() . " QR codes\n\n";

// Basic status tests
echo "🧪 Testing QR Status Logic:\n";
echo "   Restaurant QR can be scanned: " . ($restaurantQR->canBeScanned() ? 'Yes' : 'No') . "\n";
echo "   Table QR is expired: " . ($tableQR->isExpired() ? 'Yes' : 'No') . "\n";
echo "   Item QR is limit reached: " . ($itemQR->isLimitReached() ? 'Yes' : 'No') . "\n\n";

// 11. QRCodeScan test (QRCode'dan sonra)
echo "📊 Testing QRCodeScan Analytics:\n";

// Test scan data oluştur
$scan1 = QRCodeScan::create([
    'qr_code_id' => $restaurantQR->id,
    'ip_address' => '192.168.1.100',
    'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
    'device_type' => 'mobile',
    'browser' => 'Safari 15.0',
    'os' => 'iOS 15.0',
    'country' => 'US',
    'city' => 'New York',
    'referrer' => 'https://google.com',
    'scanned_at' => now()->subHours(2),
    'is_unique_visitor' => true,
    'session_id' => 'sess_' . \Str::random(10),
    'duration_on_site' => 120,
]);

echo "✅ QRCodeScan 1 created: ID = " . $scan1->id . "\n";
echo "   QR Code: " . $scan1->qrCode->code . "\n";
echo "   Device: " . $scan1->device_type . " " . $scan1->device_icon . "\n";
echo "   Browser: " . $scan1->browser_name . "\n";
echo "   Location: " . $scan1->location_display . " " . $scan1->country_flag . "\n";
echo "   Time of Day: " . $scan1->time_of_day . "\n";
echo "   Duration: " . $scan1->formatted_duration . "\n";
echo "   Is Unique: " . ($scan1->is_unique_visitor ? 'Yes' : 'No') . "\n\n";

// İkinci scan - farklı device
$scan2 = QRCodeScan::create([
    'qr_code_id' => $tableQR->id,
    'ip_address' => '192.168.1.101',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'device_type' => 'desktop',
    'browser' => 'Chrome 120.0',
    'os' => 'Windows 10.0',
    'country' => 'TR',
    'city' => 'Istanbul',
    'referrer' => 'https://facebook.com',
    'scanned_at' => now()->subMinutes(30),
    'is_unique_visitor' => true,
    'session_id' => 'sess_' . \Str::random(10),
    'duration_on_site' => 45,
]);

echo "✅ QRCodeScan 2 created: ID = " . $scan2->id . "\n";
echo "   QR Code: " . $scan2->qrCode->code . "\n";
echo "   Device: " . $scan2->device_type . " " . $scan2->device_icon . "\n";
echo "   Browser: " . $scan2->browser_name . "\n";
echo "   Location: " . $scan2->location_display . " " . $scan2->country_flag . "\n";
echo "   Duration: " . $scan2->formatted_duration . "\n\n";

// Üçüncü scan - tablet
$scan3 = QRCodeScan::create([
    'qr_code_id' => $itemQR->id,
    'ip_address' => '192.168.1.102',
    'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
    'device_type' => 'tablet',
    'browser' => 'Safari 15.0',
    'os' => 'iOS 15.0',
    'country' => 'GB',
    'city' => 'London',
    'scanned_at' => now()->subMinutes(15),
    'is_unique_visitor' => true,
    'session_id' => 'sess_' . \Str::random(10),
    'duration_on_site' => 180,
]);

echo "✅ QRCodeScan 3 created: ID = " . $scan3->id . "\n";
echo "   QR Code: " . $scan3->qrCode->code . "\n";
echo "   Device: " . $scan3->device_type . " " . $scan3->device_icon . "\n";
echo "   Location: " . $scan3->location_display . " " . $scan3->country_flag . "\n";
echo "   Duration: " . $scan3->formatted_duration . "\n\n";

// Test QRCode analytics (şimdi gerçek data ile)
echo "📈 Real QR Code Analytics:\n";
echo "   Restaurant QR:\n";
echo "     - Total scans: " . $restaurantQR->fresh()->scan_count . "\n";
echo "     - Scans today: " . $restaurantQR->getScansToday() . "\n";
echo "     - Scans this week: " . $restaurantQR->getScansThisWeek() . "\n";
echo "     - Unique scans: " . $restaurantQR->getUniqueScansCount() . "\n";

echo "   Table QR:\n";
echo "     - Total scans: " . $tableQR->fresh()->scan_count . "\n";
echo "     - Scans today: " . $tableQR->getScansToday() . "\n";
echo "     - Unique scans: " . $tableQR->getUniqueScansCount() . "\n";

echo "   Item QR:\n";
echo "     - Total scans: " . $itemQR->fresh()->scan_count . "\n";
echo "     - Scans today: " . $itemQR->getScansToday() . "\n";
echo "     - Unique scans: " . $itemQR->getUniqueScansCount() . "\n\n";

// Test static analytics methods
echo "📊 Device Type Stats:\n";
$deviceStats = QRCodeScan::getDeviceTypeStats($restaurantQR->id);
foreach ($deviceStats as $device => $count) {
    echo "   {$device}: {$count} scans\n";
}

echo "\n🌍 Country Stats:\n";
$countryStats = QRCodeScan::getCountryStats($restaurantQR->id);
foreach ($countryStats as $country => $count) {
    $flag = match($country) {
        'US' => '🇺🇸',
        'TR' => '🇹🇷', 
        'GB' => '🇬🇧',
        default => '🌍'
    };
    echo "   {$country} {$flag}: {$count} scans\n";
}

echo "\n🔗 Testing Relationships:\n";
echo "   QRCodeScan -> QRCode: " . $scan1->qrCode->type_display . "\n";
echo "   QRCode -> QRCodeScans: " . $restaurantQR->scans()->count() . " scans\n\n";

// Test device detection
echo "🧪 Testing Device Detection:\n";
$testUserAgent1 = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15';
$testUserAgent2 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0';

echo "   iPhone UA -> Device: " . QRCodeScan::detectDeviceType($testUserAgent1) . "\n";
echo "   Chrome UA -> Device: " . QRCodeScan::detectDeviceType($testUserAgent2) . "\n";
echo "   iPhone UA -> Browser: " . QRCodeScan::detectBrowser($testUserAgent1) . "\n";
echo "   Chrome UA -> Browser: " . QRCodeScan::detectBrowser($testUserAgent2) . "\n\n";

// 12. Invoice test (QRCodeScan'dan sonra)
echo "💳 Testing Invoice Model:\n";

// Invoice oluştur
$invoice = Invoice::create([
    'invoice_number' => Invoice::generateInvoiceNumber('QR'),
    'invoice_series' => 'QR',
    'user_id' => $user->id,
    'user_subscription_id' => $userSubscription->id,
    'gateway' => 'stripe',
    'gateway_invoice_id' => 'in_test_' . \Str::random(10),
    'subtotal' => 29.99,
    'tax_rate' => 0.18, // 18% KDV
    'tax_amount' => 5.40,
    'discount_amount' => 0.00,
    'total_amount' => 35.39,
    'currency' => 'USD',
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'status' => 'sent',
    'customer_data' => [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'tax_id' => '1234567890'
    ],
    'billing_address' => [
        'street' => '123 Main Street',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country' => 'US'
    ],
    'company_data' => [
        'name' => 'QR Menu Inc.',
        'address' => '456 Business Ave',
        'tax_id' => '987654321'
    ],
    'notes' => 'Monthly subscription payment for Standard Plan',
    'payment_terms' => [
        'net_days' => 30,
        'late_fee_percentage' => 1.5
    ],
    'locale' => 'en',
    'metadata' => [
        'subscription_period' => 'monthly',
        'plan_name' => 'Standard Plan'
    ]
]);

echo "✅ Invoice created: ID = " . $invoice->id . "\n";
echo "   Invoice Number: " . $invoice->invoice_number . "\n";
echo "   Customer: " . $invoice->customer_name . "\n";
echo "   Total: " . $invoice->formatted_total . "\n";
echo "   Subtotal: " . $invoice->formatted_subtotal . "\n";
echo "   Tax: " . $invoice->formatted_tax_amount . " (" . $invoice->tax_percentage . ")\n";
echo "   Status: " . $invoice->status_badge . "\n";
echo "   Due Date: " . $invoice->due_date->format('Y-m-d') . "\n";
echo "   Days Until Due: " . $invoice->days_until_due . "\n";
echo "   Is Paid: " . ($invoice->isPaid() ? 'Yes' : 'No') . "\n";
echo "   Is Overdue: " . ($invoice->isOverdue() ? 'Yes' : 'No') . "\n";
echo "   Can Be Paid: " . ($invoice->canBePaid() ? 'Yes' : 'No') . "\n";
echo "   Can Send Reminder: " . ($invoice->canSendReminder() ? 'Yes' : 'No') . "\n\n";

// Overdue invoice test
$overdueInvoice = Invoice::create([
    'invoice_number' => Invoice::generateInvoiceNumber('QR'),
    'invoice_series' => 'QR',
    'user_id' => $user->id,
    'user_subscription_id' => $userSubscription->id,
    'gateway' => 'stripe',
    'subtotal' => 99.99,
    'tax_rate' => 0.20,
    'tax_amount' => 20.00,
    'total_amount' => 119.99,
    'currency' => 'EUR',
    'invoice_date' => now()->subDays(45),
    'due_date' => now()->subDays(15), // 15 days overdue
    'status' => 'sent',
    'customer_data' => [
        'name' => $user->name,
        'email' => $user->email
    ],
    'billing_address' => [],
    'company_data' => [],
    'reminder_count' => 2,
    'last_reminder_sent_at' => now()->subDays(5),
]);

echo "✅ Overdue Invoice created: ID = " . $overdueInvoice->id . "\n";
echo "   Invoice Number: " . $overdueInvoice->invoice_number . "\n";
echo "   Total: " . $overdueInvoice->formatted_total . "\n";
echo "   Status: " . $overdueInvoice->status_badge . "\n";
echo "   Days Overdue: " . $overdueInvoice->days_overdue . "\n";
echo "   Is Overdue: " . ($overdueInvoice->isOverdue() ? 'Yes' : 'No') . "\n";
echo "   Reminder Count: " . $overdueInvoice->reminder_count . "\n";
echo "   Can Send Reminder: " . ($overdueInvoice->canSendReminder() ? 'Yes' : 'No') . "\n\n";

// Paid invoice test
$paidInvoice = Invoice::create([
    'invoice_number' => Invoice::generateInvoiceNumber('QR'),
    'invoice_series' => 'QR',
    'user_id' => $user->id,
    'user_subscription_id' => $userSubscription->id,
    'gateway' => 'iyzico',
    'subtotal' => 899.99,
    'tax_rate' => 0.18,
    'tax_amount' => 162.00,
    'total_amount' => 1061.99,
    'currency' => 'TRY',
    'invoice_date' => now()->subDays(10),
    'due_date' => now()->addDays(20),
    'status' => 'paid',
    'paid_at' => now()->subDays(5),
    'customer_data' => [
        'name' => $user->name,
        'email' => $user->email
    ],
    'billing_address' => [],
    'company_data' => [],
]);

echo "✅ Paid Invoice created: ID = " . $paidInvoice->id . "\n";
echo "   Invoice Number: " . $paidInvoice->invoice_number . "\n";
echo "   Total: " . $paidInvoice->formatted_total . "\n";
echo "   Status: " . $paidInvoice->status_badge . "\n";
echo "   Paid At: " . $paidInvoice->paid_at->format('Y-m-d H:i') . "\n";
echo "   Is Paid: " . ($paidInvoice->isPaid() ? 'Yes' : 'No') . "\n\n";

// Test relationships
echo "🔗 Testing Invoice Relationships:\n";
echo "   Invoice -> User: " . $invoice->user->name . "\n";
echo "   Invoice -> Subscription: " . $invoice->userSubscription->subscriptionPlan->localized_name . "\n";
echo "   User has " . $user->fresh()->invoices()->count() . " invoices\n";
echo "   Subscription has " . $userSubscription->fresh()->invoices()->count() . " invoices\n\n";

// Test status changes
echo "🔄 Testing Status Changes:\n";
$invoice->markAsViewed();
echo "   Invoice marked as viewed: " . $invoice->fresh()->status . "\n";

$invoice->markAsPaid();
echo "   Invoice marked as paid: " . $invoice->fresh()->status . "\n";
echo "   Paid at: " . $invoice->fresh()->paid_at->format('Y-m-d H:i') . "\n\n";

// Test invoice number generation
echo "🔢 Testing Invoice Number Generation:\n";
$number1 = Invoice::generateInvoiceNumber('QR');
$number2 = Invoice::generateInvoiceNumber('QR');
$number3 = Invoice::generateInvoiceNumber('TEST');

echo "   Generated Numbers:\n";
echo "     QR Series: " . $number1 . "\n";
echo "     QR Series: " . $number2 . "\n";
echo "     TEST Series: " . $number3 . "\n\n";

// Test scopes
echo "📊 Testing Invoice Scopes:\n";
echo "   Total Invoices: " . Invoice::count() . "\n";
echo "   Paid Invoices: " . Invoice::paid()->count() . "\n";
echo "   Unpaid Invoices: " . Invoice::unpaid()->count() . "\n";
echo "   Overdue Invoices: " . Invoice::overdue()->count() . "\n";
echo "   This Month: " . Invoice::thisMonth()->count() . "\n";
echo "   USD Invoices: " . Invoice::inCurrency('USD')->count() . "\n";
echo "   EUR Invoices: " . Invoice::inCurrency('EUR')->count() . "\n";
echo "   TRY Invoices: " . Invoice::inCurrency('TRY')->count() . "\n\n";

// Test revenue calculations
echo "💰 Testing Revenue Calculations:\n";
echo "   Total Revenue (30 days): $" . number_format(Invoice::getTotalRevenue(), 2) . "\n";
echo "   USD Revenue: $" . number_format(Invoice::getTotalRevenue('USD'), 2) . "\n";
echo "   EUR Revenue: €" . number_format(Invoice::getTotalRevenue('EUR'), 2) . "\n";
echo "   TRY Revenue: ₺" . number_format(Invoice::getTotalRevenue('TRY'), 2) . "\n\n";

// 13. InvoiceItem test (Invoice'dan sonra) - SON TEST!
echo "📄 Testing InvoiceItem Model (Final Model!):\n";

// Subscription item
$subscriptionItem = InvoiceItem::create([
    'invoice_id' => $invoice->id,
    'description' => [
        'en' => 'Standard Plan - Monthly Subscription',
        'tr' => 'Standart Plan - Aylık Abonelik'
    ],
    'item_code' => 'PLAN_STANDARD_MONTHLY',
    'item_type' => 'subscription',
    'quantity' => 1,
    'unit_price' => 29.99,
    'total_price' => 29.99,
    'period_start' => now(),
    'period_end' => now()->addMonth(),
    'tax_rate' => 0.18,
    'tax_amount' => 5.40,
    'is_tax_exempt' => false,
    'sort_order' => 1,
    'metadata' => [
        'plan_id' => $plan->id,
        'billing_cycle' => 'monthly'
    ]
]);

echo "✅ Subscription Item created: ID = " . $subscriptionItem->id . "\n";
echo "   Description: " . $subscriptionItem->localized_description . "\n";
echo "   Type: " . $subscriptionItem->type_display . " " . $subscriptionItem->type_icon . "\n";
echo "   Quantity: " . $subscriptionItem->quantity . "\n";
echo "   Unit Price: " . $subscriptionItem->formatted_unit_price . "\n";
echo "   Total Price: " . $subscriptionItem->formatted_total_price . "\n";
echo "   Tax Amount: " . $subscriptionItem->formatted_tax_amount . " (" . $subscriptionItem->tax_percentage . ")\n";
echo "   Period: " . $subscriptionItem->period_display . "\n";
echo "   Duration: " . $subscriptionItem->period_duration . " days\n";
echo "   Is Subscription: " . ($subscriptionItem->isSubscription() ? 'Yes' : 'No') . "\n";
echo "   Has Tax: " . ($subscriptionItem->hasTax() ? 'Yes' : 'No') . "\n\n";

// Setup fee item
$setupItem = InvoiceItem::createSetupFee(
    $invoice, 
    50.00, 
    ['en' => 'One-time Setup Fee', 'tr' => 'Tek Seferlik Kurulum Ücreti']
);

echo "✅ Setup Fee Item created: ID = " . $setupItem->id . "\n";
echo "   Description: " . $setupItem->localized_description . "\n";
echo "   Type: " . $setupItem->type_display . " " . $setupItem->type_icon . "\n";
echo "   Amount: " . $setupItem->formatted_total_price . "\n\n";

// Discount item
$discountItem = InvoiceItem::createDiscount(
    $invoice,
    10.00,
    ['en' => 'New Customer Discount', 'tr' => 'Yeni Müşteri İndirimi']
);

echo "✅ Discount Item created: ID = " . $discountItem->id . "\n";
echo "   Description: " . $discountItem->localized_description . "\n";
echo "   Type: " . $discountItem->type_display . " " . $discountItem->type_icon . "\n";
echo "   Amount: " . $discountItem->formatted_total_price . "\n";
echo "   Is Discount: " . ($discountItem->isDiscount() ? 'Yes' : 'No') . "\n\n";

// Add-on item
$addonItem = InvoiceItem::create([
    'invoice_id' => $invoice->id,
    'description' => [
        'en' => 'Premium Analytics Add-on',
        'tr' => 'Premium Analitik Eklentisi'
    ],
    'item_code' => 'ADDON_ANALYTICS',
    'item_type' => 'addon',
    'quantity' => 1,
    'unit_price' => 15.00,
    'total_price' => 15.00,
    'tax_rate' => 0.18,
    'sort_order' => 2,
]);

$addonItem->calculateTax();

echo "✅ Add-on Item created: ID = " . $addonItem->id . "\n";
echo "   Description: " . $addonItem->localized_description . "\n";
echo "   Type: " . $addonItem->type_display . " " . $addonItem->type_icon . "\n";
echo "   Price: " . $addonItem->formatted_total_price . "\n";
echo "   Tax: " . $addonItem->formatted_tax_amount . "\n";
echo "   Net Amount: " . $addonItem->formatted_net_amount . "\n\n";

// Test relationships
echo "🔗 Testing InvoiceItem Relationships:\n";
echo "   Item -> Invoice: " . $subscriptionItem->invoice->invoice_number . "\n";
echo "   Invoice has " . $invoice->fresh()->invoiceItems()->count() . " items\n\n";

// Test scopes
echo "📊 Testing InvoiceItem Scopes:\n";
echo "   Total Items: " . InvoiceItem::count() . "\n";
echo "   Subscription Items: " . InvoiceItem::subscriptions()->count() . "\n";
echo "   Add-on Items: " . InvoiceItem::addons()->count() . "\n";
echo "   Discount Items: " . InvoiceItem::discounts()->count() . "\n";
echo "   Tax Exempt Items: " . InvoiceItem::taxExempt()->count() . "\n\n";

// Test calculations
echo "🧮 Testing Calculations:\n";
$testItem = InvoiceItem::create([
    'invoice_id' => $invoice->id,
    'description' => ['en' => 'Test Item'],
    'item_type' => 'custom',
    'quantity' => 3,
    'unit_price' => 25.00,
    'tax_rate' => 0.20,
    'sort_order' => 99,
]);

echo "   Before calculations:\n";
echo "     Total: " . $testItem->formatted_total_price . "\n";
echo "     Tax: " . $testItem->formatted_tax_amount . "\n";

$testItem->calculateTotal();
$testItem->calculateTax();

echo "   After calculations:\n";
echo "     Total: " . $testItem->fresh()->formatted_total_price . "\n";
echo "     Tax: " . $testItem->fresh()->formatted_tax_amount . "\n";

$testItem->applyPercentageDiscount(20); // 20% discount

echo "   After 20% discount:\n";
echo "     Discount: " . $testItem->fresh()->formatted_discount_amount . "\n";
echo "     Net: " . $testItem->fresh()->formatted_net_amount . "\n";
echo "     Tax: " . $testItem->fresh()->formatted_tax_amount . "\n\n";

// Test invoice total recalculation
echo "💰 Invoice Total After Items:\n";
$invoice->fresh()->calculateTotals();
echo "   Subtotal: " . $invoice->fresh()->formatted_subtotal . "\n";
echo "   Tax: " . $invoice->fresh()->formatted_tax_amount . "\n";
echo "   Total: " . $invoice->fresh()->formatted_total . "\n\n";

echo "🎉 ALL 12 MODELS COMPLETED SUCCESSFULLY! 🎉\n";
echo "📊 Final Model Count Summary:\n";
echo "   Languages: " . Language::count() . "\n";
echo "   Subscription Plans: " . SubscriptionPlan::count() . "\n";
echo "   Users: " . User::count() . "\n";
echo "   User Subscriptions: " . UserSubscription::count() . "\n";
echo "   Restaurants: " . Restaurant::count() . "\n";
echo "   Branches: " . Branch::count() . "\n";
echo "   Menu Categories: " . MenuCategory::count() . "\n";
echo "   Menu Items: " . MenuItem::count() . "\n";
echo "   Tables: " . Table::count() . "\n";
echo "   QR Codes: " . QRCode::count() . "\n";
echo "   QR Code Scans: " . QRCodeScan::count() . "\n";
echo "   Invoices: " . Invoice::count() . "\n";
echo "   Invoice Items: " . InvoiceItem::count() . "\n";
echo "\n🚀 QR Menu Database Models: COMPLETE! 🚀\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Foreign key constraint'leri tekrar aç (hata durumunda)
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
}