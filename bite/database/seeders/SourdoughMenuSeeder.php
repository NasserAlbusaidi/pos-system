<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class SourdoughMenuSeeder extends Seeder
{
    /**
     * Seed the Sourdough Oman shop with full bakery menu.
     *
     * Idempotent: skips if 'sourdough' shop already exists.
     */
    public function run(): void
    {
        if (Shop::where('slug', 'sourdough')->exists()) {
            $this->command?->info('Sourdough shop already exists — skipping.');

            return;
        }

        $shop = Shop::create([
            'name' => 'Sourdough Oman',
            'slug' => 'sourdough',
            'currency_code' => 'OMR',
            'currency_symbol' => 'ر.ع.',
            'currency_decimals' => 3,
            'tax_rate' => 0,
            'branding' => [
                'paper' => '#F5F0E8',
                'accent' => '#C4975A',
                'ink' => '#2C2520',
                'onboarding_completed' => true,
            ],
        ]);

        // trial_ends_at and status are not in $fillable — set explicitly
        $shop->status = 'active';
        $shop->trial_ends_at = now()->addYears(10);
        $shop->save();

        User::forceCreate([
            'shop_id' => $shop->id,
            'name' => 'Sourdough Admin',
            'email' => 'admin@sourdough.om',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->seedForShop($shop);
    }

    public function seedForShop(Shop $shop): void
    {
        // ── Categories ───────────────────────────────────────────
        $breads = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Breads',
            'name_ar' => 'خبز',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $pastries = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Pastries',
            'name_ar' => 'معجنات',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $sandwiches = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Sandwiches',
            'name_ar' => 'ساندويتشات',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $salads = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Salads & Bowls',
            'name_ar' => 'سلطات وأطباق',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $beverages = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Beverages',
            'name_ar' => 'مشروبات',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $desserts = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Desserts',
            'name_ar' => 'حلويات',
            'sort_order' => 6,
            'is_active' => true,
        ]);

        // ── Products ─────────────────────────────────────────────

        // Breads — 6 items
        $breadItems = [
            [
                'name_en' => 'Sourdough loaf',
                'name_ar' => 'خبز العجين المخمر',
                'description_en' => 'Our signature slow-fermented sourdough loaf, baked fresh daily with a golden crust',
                'description_ar' => 'رغيف عجين مخمر يدوي مخبوز يومياً بقشرة ذهبية مميزة',
                'price' => 2.500,
            ],
            [
                'name_en' => 'French baguette',
                'name_ar' => 'خبز الباغيت الفرنسي',
                'description_en' => 'Classic French baguette with a crisp crust and light airy crumb',
                'description_ar' => 'خبز باغيت فرنسي كلاسيكي بقشرة مقرمشة وداخل هش خفيف',
                'price' => 0.800,
            ],
            [
                'name_en' => 'Ciabatta',
                'name_ar' => 'خبز الشياباتا',
                'description_en' => 'Italian flatbread with an open, chewy crumb and olive oil richness',
                'description_ar' => 'خبز إيطالي مسطح بملمس مطاطي وغني بزيت الزيتون',
                'price' => 1.200,
            ],
            [
                'name_en' => 'Focaccia',
                'name_ar' => 'خبز الفوكاتشيا',
                'description_en' => 'Herb-topped flatbread drizzled with extra virgin olive oil and sea salt',
                'description_ar' => 'خبز مسطح مزين بالأعشاب وزيت الزيتون البكر وملح البحر',
                'price' => 1.500,
            ],
            [
                'name_en' => 'Multigrain loaf',
                'name_ar' => 'خبز الحبوب المتعددة',
                'description_en' => 'Hearty loaf packed with seeds and wholegrains for a nutty, wholesome flavour',
                'description_ar' => 'رغيف مليء بالبذور والحبوب الكاملة بنكهة غنية ومقوية',
                'price' => 2.200,
            ],
            [
                'name_en' => 'Olive bread',
                'name_ar' => 'خبز الزيتون',
                'description_en' => 'Rustic sourdough studded with Kalamata olives and rosemary',
                'description_ar' => 'خبز عجين مخمر ريفي محشو بزيتون الكالاماتا وإكليل الجبل',
                'price' => 2.000,
            ],
        ];

        // Pastries — 6 items
        $pastryItems = [
            [
                'name_en' => 'Butter croissant',
                'name_ar' => 'كرواسون بالزبدة',
                'description_en' => 'Flaky, buttery layers baked to a deep golden finish',
                'description_ar' => 'طبقات هشة وزبدانية مخبوزة حتى اللون الذهبي العميق',
                'price' => 0.700,
            ],
            [
                'name_en' => 'Pain au chocolat',
                'name_ar' => 'خبز الشوكولاتة',
                'description_en' => 'Croissant dough wrapped around two sticks of dark chocolate',
                'description_ar' => 'عجينة الكرواسون ملفوفة حول عصيّتين من الشوكولاتة الداكنة',
                'price' => 0.900,
            ],
            [
                'name_en' => 'Almond danish',
                'name_ar' => 'دانش باللوز',
                'description_en' => 'Light pastry filled with almond cream and topped with toasted flakes',
                'description_ar' => 'معجنات خفيفة محشوة بكريمة اللوز ومزينة برقائق محمصة',
                'price' => 1.100,
            ],
            [
                'name_en' => 'Cinnamon roll',
                'name_ar' => 'لفائف القرفة',
                'description_en' => 'Soft swirled roll packed with brown sugar, cinnamon, and vanilla glaze',
                'description_ar' => 'لفيفة طرية محشوة بالسكر البني والقرفة مع طلاء الفانيليا',
                'price' => 1.200,
            ],
            [
                'name_en' => 'Cheese roll',
                'name_ar' => 'لفائف الجبن',
                'description_en' => 'Warm brioche roll stuffed with melted cheese — a bakery staple',
                'description_ar' => 'لفيفة بريوش دافئة محشوة بالجبن المذاب',
                'price' => 0.800,
            ],
            [
                'name_en' => 'Zaatar manakeesh',
                'name_ar' => 'مناقيش الزعتر',
                'description_en' => 'Soft flatbread topped with a fragrant zaatar and olive oil blend',
                'description_ar' => 'خبز مسطح طري مغطى بمزيج زعتر عطري وزيت الزيتون',
                'price' => 0.600,
            ],
        ];

        // Sandwiches — 6 items
        $sandwichItems = [
            [
                'name_en' => 'Grilled chicken sandwich',
                'name_ar' => 'ساندويتش الدجاج المشوي',
                'description_en' => 'Tender grilled chicken breast with avocado, tomato, and garlic aioli on sourdough',
                'description_ar' => 'صدر دجاج مشوي طري مع الأفوكادو والطماطم وصلصة الثوم على خبز العجين المخمر',
                'price' => 2.800,
            ],
            [
                'name_en' => 'Turkey club',
                'name_ar' => 'ساندويتش التركي',
                'description_en' => 'Smoked turkey, crispy bacon, lettuce, tomato, and mayo on toasted sourdough',
                'description_ar' => 'ديك رومي مدخن مع خس وطماطم ومايونيز على خبز العجين المحمص',
                'price' => 3.000,
            ],
            [
                'name_en' => 'Caprese sandwich',
                'name_ar' => 'ساندويتش كابريزي',
                'description_en' => 'Fresh mozzarella, heirloom tomatoes, basil, and balsamic glaze on ciabatta',
                'description_ar' => 'موزاريلا طازجة وطماطم وريحان وصلصة البلسميك على خبز الشياباتا',
                'price' => 2.200,
            ],
            [
                'name_en' => 'Tuna melt',
                'name_ar' => 'ساندويتش التونة المذابة',
                'description_en' => 'Herb tuna salad topped with melted cheddar, grilled on sourdough',
                'description_ar' => 'سلطة تونة بالأعشاب مع جبن شيدر مذاب على خبز العجين المشوي',
                'price' => 2.000,
            ],
            [
                'name_en' => 'Falafel wrap',
                'name_ar' => 'لفائف الفلافل',
                'description_en' => 'Crispy falafel with hummus, marinated vegetables, and tahini in soft flatbread',
                'description_ar' => 'فلافل مقرمشة مع حمص وخضروات متبلة وطحينة في خبز مسطح طري',
                'price' => 1.800,
            ],
            [
                'name_en' => 'Halloumi sandwich',
                'name_ar' => 'ساندويتش الحلوم',
                'description_en' => 'Grilled halloumi with roasted peppers, spinach, and pesto on focaccia',
                'description_ar' => 'جبن حلوم مشوي مع فلفل محمص وسبانخ وبيستو على خبز الفوكاتشيا',
                'price' => 2.500,
            ],
        ];

        // Salads & Bowls — 5 items
        $saladItems = [
            [
                'name_en' => 'Caesar salad',
                'name_ar' => 'سلطة سيزر',
                'description_en' => 'Crisp romaine, parmesan shavings, and house-made caesar dressing',
                'description_ar' => 'خس روماني طازج وجبن البارميزان مع صلصة السيزر المنزلية',
                'price' => 2.200,
            ],
            [
                'name_en' => 'Greek salad',
                'name_ar' => 'سلطة يونانية',
                'description_en' => 'Cucumber, tomato, Kalamata olives, red onion, and feta in lemon vinaigrette',
                'description_ar' => 'خيار وطماطم وزيتون الكالاماتا وبصل أحمر وفيتا مع خل الليمون',
                'price' => 2.000,
            ],
            [
                'name_en' => 'Quinoa bowl',
                'name_ar' => 'طبق الكينوا',
                'description_en' => 'Tri-colour quinoa with roasted vegetables, avocado, and tahini dressing',
                'description_ar' => 'كينوا ثلاثية الألوان مع خضروات مشوية وأفوكادو وصلصة الطحينة',
                'price' => 3.200,
            ],
            [
                'name_en' => 'Grain bowl',
                'name_ar' => 'طبق الحبوب',
                'description_en' => 'Farro, kale, roasted sweet potato, and a poached egg with lemon dressing',
                'description_ar' => 'حبوب الفارو مع الكيل والبطاطا الحلوة المشوية وبيضة مسلوقة',
                'price' => 3.500,
            ],
            [
                'name_en' => 'Soup of the day',
                'name_ar' => 'شوربة اليوم',
                'description_en' => 'Seasonal soup from the chef served with a slice of sourdough bread',
                'description_ar' => 'شوربة موسمية من اختيار الشيف مقدمة مع شريحة من خبز العجين المخمر',
                'price' => 1.800,
            ],
        ];

        // Beverages — 6 items
        $beverageItems = [
            [
                'name_en' => 'Espresso',
                'name_ar' => 'إسبريسو',
                'description_en' => 'Single origin espresso pulled short and strong with a rich crema',
                'description_ar' => 'إسبريسو أحادي المصدر قوي وغني بالكريمة',
                'price' => 0.800,
            ],
            [
                'name_en' => 'Cappuccino',
                'name_ar' => 'كابتشينو',
                'description_en' => 'Equal parts espresso, steamed milk, and velvety microfoam',
                'description_ar' => 'أجزاء متساوية من الإسبريسو والحليب المبخر والرغوة الناعمة',
                'price' => 1.200,
            ],
            [
                'name_en' => 'Latte',
                'name_ar' => 'لاتيه',
                'description_en' => 'Smooth espresso topped with silky steamed milk and a thin layer of foam',
                'description_ar' => 'إسبريسو ناعم مع حليب مبخر حريري وطبقة رقيقة من الرغوة',
                'price' => 1.500,
            ],
            [
                'name_en' => 'Fresh juice',
                'name_ar' => 'عصير طازج',
                'description_en' => 'Cold-pressed seasonal fruit juice — ask for today\'s selection',
                'description_ar' => 'عصير فواكه موسمية معصور على البارد — اسأل عن تشكيلة اليوم',
                'price' => 1.500,
            ],
            [
                'name_en' => 'Iced tea',
                'name_ar' => 'شاي مثلج',
                'description_en' => 'Cold-brewed hibiscus and mint tea lightly sweetened with honey',
                'description_ar' => 'شاي الكركدي والنعناع المبرد محلى قليلاً بالعسل',
                'price' => 1.000,
            ],
            [
                'name_en' => 'Hot chocolate',
                'name_ar' => 'شوكولاتة ساخنة',
                'description_en' => 'Rich Belgian dark chocolate melted into steamed milk with a hint of vanilla',
                'description_ar' => 'شوكولاتة بلجيكية داكنة غنية مذابة في حليب مبخر مع لمسة فانيليا',
                'price' => 1.800,
            ],
        ];

        // Desserts — 4 items
        $dessertItems = [
            [
                'name_en' => 'Tiramisu',
                'name_ar' => 'تيراميسو',
                'description_en' => 'Classic Italian dessert with espresso-soaked ladyfingers and mascarpone cream',
                'description_ar' => 'حلوى إيطالية كلاسيكية من بسكويت مغموس بالإسبريسو وكريمة الماسكاربوني',
                'price' => 2.200,
            ],
            [
                'name_en' => 'Cheesecake',
                'name_ar' => 'تشيز كيك',
                'description_en' => 'New York-style baked cheesecake with a buttery biscuit base and berry compote',
                'description_ar' => 'تشيز كيك مخبوز على الطريقة الأمريكية مع قاعدة بسكويت زبداني وكومبوت التوت',
                'price' => 2.500,
            ],
            [
                'name_en' => 'Dark chocolate brownie',
                'name_ar' => 'براوني الشوكولاتة الداكنة',
                'description_en' => 'Dense, fudgy brownie made with 70% dark chocolate and sea salt flakes',
                'description_ar' => 'براوني كثيف وناعم مصنوع من شوكولاتة 70% داكنة مع رقائق ملح البحر',
                'price' => 1.500,
            ],
            [
                'name_en' => 'Fruit tart',
                'name_ar' => 'تارت الفاكهة',
                'description_en' => 'Crisp pastry shell filled with vanilla custard and topped with seasonal fresh fruit',
                'description_ar' => 'قشرة معجنات مقرمشة محشوة بكريمة الفانيليا ومزينة بفاكهة موسمية طازجة',
                'price' => 1.800,
            ],
        ];

        // ── Create products using explicit shop_id assignment (shop_id is guarded) ──
        $this->createProducts($shop, $breads, $breadItems);
        $this->createProducts($shop, $pastries, $pastryItems);
        $this->createProducts($shop, $sandwiches, $sandwichItems);
        $this->createProducts($shop, $salads, $saladItems);
        $this->createProducts($shop, $beverages, $beverageItems);
        $this->createProducts($shop, $desserts, $dessertItems);
    }

    /**
     * Create products for a category, setting shop_id explicitly since it is guarded.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createProducts(Shop $shop, Category $category, array $items): void
    {
        foreach ($items as $index => $data) {
            $product = new Product([
                'category_id' => $category->id,
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'description_en' => $data['description_en'],
                'description_ar' => $data['description_ar'],
                'price' => $data['price'],
                'is_available' => true,
                'is_visible' => true,
                'sort_order' => $index + 1,
            ]);

            // shop_id is guarded — must be set explicitly to prevent tenant isolation bypass
            $product->shop_id = $shop->id;
            $product->save();
        }
    }
}
