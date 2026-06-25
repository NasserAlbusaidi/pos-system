const ASSET = "./assets/hopresso";
const BRAND_ASSET = "./assets/brand";

const screens = [
  [1, "01. Splashscreen", "splash"],
  [2, "02. Onboarding #1", "onboarding-1"],
  [3, "03. Onboarding #2", "onboarding-2"],
  [4, "04. Onboarding #3", "onboarding-3"],
  [5, "05. Login", "login"],
  [6, "06. Login - Filled", "login-filled"],
  [7, "07. Login - Filled - Error", "login-error"],
  [8, "08. Verification Email", "verification"],
  [9, "09. Verification Email - Filled", "verification-filled"],
  [10, "10. Forgot Password - Input Email", "forgot-email"],
  [11, "11. Forgot Password - Create Password", "forgot-password"],
  [12, "12. Register", "register"],
  [13, "13. Register - Filled", "register-filled"],
  [14, "14. Register - Filled - Error", "register-error"],
  [15, "15. Home for Guest #1", "home-guest-1"],
  [16, "16. Home for Guest #2", "home-guest-2"],
  [17, "17. Home for Guest #3", "home-guest-3"],
  [18, "18. Alert for login", "login-alert"],
  [19, "19. Home #1", "home"],
  [20, "20. Home #2", "home-pickup"],
  [21, "21. Home #3", "home-delivery"],
  [22, "22. Notification", "notifications"],
  [23, "23. Search Outlet", "search-outlet"],
  [24, "24. Detail Outlet", "detail-outlet"],
  [25, "25. Order", "order"],
  [26, "26. Search - Product", "search-product"],
  [27, "27. Search - not found product", "search-empty"],
  [28, "28. Detail Product", "product-detail"],
  [29, "29. Order - after add product", "order-cart"],
  [30, "30. Checkout - Pick Up", "checkout-pickup"],
  [31, "31. Checkout - Pick Up - Complete", "checkout-pickup-complete"],
  [32, "32. Pick Up - On Placed", "pickup-placed"],
  [33, "33. Pick Up Order - Accepted", "pickup-accepted"],
  [34, "34. Pick Up Order - In progress", "pickup-progress"],
  [35, "35. Pick Up Order - Ready Pick Up", "pickup-ready"],
  [36, "36. Order Detail - Pick Up", "pickup-detail"],
  [37, "37. Checkout - Delivery", "checkout-delivery"],
  [38, "38. Checkout - Delivery - Complete", "checkout-delivery-complete"],
  [39, "39. Delivery Order - On Placed", "delivery-placed"],
  [40, "40. Delivery Order - Accepted", "delivery-accepted"],
  [41, "41. Delivery Order - In progress", "delivery-progress"],
  [42, "42. Delivery Order - Delivered", "delivery-delivered"],
  [43, "43. Order Detail - Delivery", "delivery-detail"],
  [44, "44. Voucher", "voucher"],
  [45, "45. Method Payment", "payment"],
  [46, "46. Checkout - Delivery", "checkout-delivery-compact"],
  [47, "47. Success", "success"],
  [48, "48. Unsuccess", "unsuccess"],
  [49, "49. Scan QR", "scan"],
  [50, "50. Scan QR - flash", "scan-flash"],
  [51, "51. My QR Code", "my-qr"],
  [52, "52. History", "history"],
  [53, "53. Favorite", "favorite"],
  [54, "54. Profile", "profile"],
  [55, "55. Edit Profile", "edit-profile"],
  [56, "56. Adress", "address"],
  [57, "57. Card", "card"],
  [58, "58. Language", "language"],
  [59, "59. Privacy & Policy", "privacy"],
  [60, "60. Termj of Service", "terms"],
  [61, "61. Help Center", "help"],
  [62, "62. App Invite", "app-invite"],
].map(([number, figmaName, route]) => ({ number, figmaName, route }));

const state = {
  route: "language",
  orderMode: "Dine In",
  paymentMethod: "Pay at counter",
  language: localStorage.getItem("hopresso-language") || "",
  selectedCategory: "All",
  selectedSize: "Regular",
  selectedMilk: "Standard milk",
  paymentOpen: false,
  voucherCode: "",
  voucherApplied: false,
  flash: false,
  favoriteOutlet: true,
  selectedProductId: "americano",
  cart: [],
  search: "",
  user: {
    name: "Leslie Alexander",
    email: "leslie.alexander@example.com",
    phone: "+1 555 012 889",
  },
};

const menuCatalog = [
  {
    category: "Coffee Classics",
    items: [
      "Espresso",
      "Double Espresso",
      "Americano",
      "Long Black",
      "Macchiato",
      "Cortado",
      "Flat White",
      "Cappuccino",
      "Latte",
      "Mocha",
      "White Mocha",
      "Affogato",
    ],
  },
  {
    category: "Signature Lattes",
    items: [
      "Vanilla Bean Latte",
      "Caramel Latte",
      "Hazelnut Latte",
      "Spanish Latte",
      "Pistachio Latte",
      "Honey Cinnamon Latte",
      "Coconut Latte",
      "Salted Caramel Latte",
      "Brown Sugar Latte",
      "Maple Latte",
      "French Vanilla Latte",
      "Toasted Almond Latte",
    ],
  },
  {
    category: "Nitro Bar Inspired Signatures",
    items: [
      "Blueberry Latte",
      "Orange Vanilla Latte",
      "Cookie Butter Latte",
      "Caramelized Banana Latte",
      "Dirty Wafer Latte",
      "Cherry Vanilla Latte",
      "Strawberry Milk Latte",
      "Pistachio Cream Latte",
      "Salted Maple Latte",
      "Coconut Cloud Latte",
      "Tiramisu Latte",
      "Toasted Marshmallow Latte",
      "Red Velvet Latte",
      "Peanut Butter Mocha",
      "White Chocolate Raspberry Latte",
      "Cinnamon Roll Latte",
      "Banana Cream Latte",
      "Crème Brûlée Latte",
      "Chocolate Covered Strawberry Latte",
      "Dubai Chocolate Latte",
    ],
  },
  {
    category: "Cold Coffee",
    items: [
      "Iced Americano",
      "Iced Latte",
      "Iced Spanish Latte",
      "Iced Pistachio Latte",
      "Iced Mocha",
      "Iced Caramel Latte",
      "Iced Vanilla Latte",
      "Iced Brown Sugar Latte",
    ],
  },
  {
    category: "Cold Brew & Nitro",
    items: [
      "Cold Brew",
      "Vanilla Cream Cold Brew",
      "Nitro Cold Brew",
      "Vanilla Cream Nitro",
      "Maple Sea Salt Nitro",
      "Coconut Nitro",
      "Mocha Nitro",
      "Brown Sugar Nitro",
      "Coffee Tonic",
    ],
  },
  {
    category: "Matcha Classics",
    items: [
      "Traditional Matcha",
      "Matcha Latte",
      "Iced Matcha Latte",
      "Vanilla Matcha Latte",
      "Honey Matcha Latte",
      "Coconut Matcha Latte",
    ],
  },
  {
    category: "Signature Matcha",
    items: [
      "Strawberry Matcha Latte",
      "Mango Matcha Latte",
      "Blueberry Matcha Latte",
      "Orange Cream Matcha",
      "Cookie Butter Matcha",
      "Lavender Matcha",
      "White Chocolate Matcha",
      "Pistachio Matcha",
      "Watermelon Lemonade Matcha",
      "Strawberry Shortcake Matcha",
      "Banana Cream Matcha",
      "Cherry Blossom Matcha",
      "Peach Matcha Latte",
    ],
  },
  {
    category: "Refreshers & Lemonades",
    items: [
      "Blueberry Lemonade",
      "Strawberry Lemonade",
      "Mango Lemonade",
      "Passionfruit Lemonade",
      "Watermelon Cooler",
      "Peach Iced Tea",
      "Hibiscus Lemonade",
      "Tropical Citrus Refresher",
    ],
  },
  {
    category: "Seasonal Specials",
    items: [
      "Pumpkin Spice Latte",
      "S'mores Latte",
      "Ginger Citrus Latte",
      "Neapolitan Latte",
      "Maple Pecan Latte",
      "Honey Lavender Latte",
      "Campfire Mocha",
      "Salted Honey Cold Brew",
      "Winter Spice Matcha",
      "Rose Pistachio Latte",
      "Cinnamon Apple Latte",
      "Brownie Batter Latte",
    ],
  },
];

function productId(name) {
  return name
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/&/g, "and")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function productImage(name, category) {
  const text = `${name} ${category}`.toLowerCase();
  if (
    text.includes("matcha") ||
    text.includes("lemonade") ||
    text.includes("tea") ||
    text.includes("refresher") ||
    text.includes("cooler") ||
    text.includes("hibiscus") ||
    text.includes("watermelon") ||
    text.includes("mango") ||
    text.includes("peach")
  ) {
    return `${ASSET}/sparkling-tea.png`;
  }
  if (text.includes("cold brew") || text.includes("nitro") || text.includes("iced") || text.includes("tonic")) {
    return `${ASSET}/iced-latte.png`;
  }
  if (
    text.includes("caramel") ||
    text.includes("mocha") ||
    text.includes("chocolate") ||
    text.includes("cookie") ||
    text.includes("tiramisu") ||
    text.includes("red velvet") ||
    text.includes("brûlée") ||
    text.includes("brulee") ||
    text.includes("brownie") ||
    text.includes("campfire")
  ) {
    return `${ASSET}/caramel-cream.png`;
  }
  if (
    text.includes("latte") ||
    text.includes("flat white") ||
    text.includes("cappuccino") ||
    text.includes("cortado") ||
    text.includes("macchiato") ||
    text.includes("affogato")
  ) {
    return `${ASSET}/coffee-latte-top.png`;
  }
  return `${ASSET}/americano.png`;
}

function productSubtitle(name, category) {
  if (category.includes("Matcha")) return "Ceremonial matcha blend";
  if (category.includes("Refreshers")) return "Bright fruit refresher";
  if (category.includes("Cold Brew")) return "Slow-steeped coffee base";
  if (category === "Cold Coffee") return "Served cold over ice";
  if (category.includes("Signature")) return "Hopresso crafted signature";
  if (name.includes("Mocha")) return "Chocolate espresso blend";
  return "Hopresso coffee classic";
}

function productPrice(category, index, name) {
  const baseByCategory = {
    "Coffee Classics": 1.1,
    "Signature Lattes": 1.8,
    "Nitro Bar Inspired Signatures": 2.1,
    "Cold Coffee": 1.7,
    "Cold Brew & Nitro": 1.9,
    "Matcha Classics": 1.8,
    "Signature Matcha": 2.1,
    "Refreshers & Lemonades": 1.5,
    "Seasonal Specials": 2.2,
  };
  let price = baseByCategory[category] + (index % 4) * 0.1;
  if (name.includes("Double") || name.includes("Nitro") || name.includes("Dubai")) price += 0.2;
  if (name.includes("Affogato") || name.includes("Chocolate") || name.includes("Pistachio")) price += 0.15;
  return Number(price.toFixed(3));
}

function productCalories(category, index) {
  const baseByCategory = {
    "Coffee Classics": 80,
    "Signature Lattes": 210,
    "Nitro Bar Inspired Signatures": 240,
    "Cold Coffee": 190,
    "Cold Brew & Nitro": 130,
    "Matcha Classics": 170,
    "Signature Matcha": 220,
    "Refreshers & Lemonades": 120,
    "Seasonal Specials": 250,
  };
  return `${baseByCategory[category] + (index % 5) * 15} kcal`;
}

const products = menuCatalog.flatMap((section) =>
  section.items.map((name, index) => ({
    id: productId(name),
    name,
    subtitle: productSubtitle(name, section.category),
    price: productPrice(section.category, index, name),
    image: productImage(name, section.category),
    category: section.category,
    calories: productCalories(section.category, index),
    rating: (4.6 + (index % 4) * 0.1).toFixed(1),
    description: `${name} from the ${section.category} menu, prepared for the Hopresso QR ordering flow.`,
  })),
);

const outlets = [
  { name: "Outlet 1", place: "Muscat, Oman", distance: "1.2 km", image: `${ASSET}/storefront.png` },
  { name: "The Nitro Bar", place: "Qurum Beach Road", distance: "2.8 km", image: `${ASSET}/cafe-interior.jpg` },
  { name: "Hopresso Mall", place: "Avenues Mall", distance: "4.1 km", image: `${ASSET}/map-square.png` },
];

const categories = ["All", ...menuCatalog.map((section) => section.category)];

const featuredProductIds = [
  "americano",
  "spanish-latte",
  "dubai-chocolate-latte",
  "iced-spanish-latte",
  "nitro-cold-brew",
  "strawberry-matcha-latte",
  "blueberry-lemonade",
  "pumpkin-spice-latte",
];

const highlightedProductId = "dubai-chocolate-latte";
const paymentMethods = ["Pay at counter", "Online payment"];

const notifications = [
  ["Order Accepted", "Your pickup order has been accepted by Outlet 1.", "2 min ago"],
  ["New Flavors Just Dropped", "Try the new caramel cream menu this week.", "Today"],
  ["Voucher Ready", "You have one voucher ready for your next checkout.", "Yesterday"],
];

const OMANI_RIAL_SIGN = "\u20C4";

function money(value) {
  return `<span class="money"><span class="rial-symbol" aria-label="Omani Rial">${OMANI_RIAL_SIGN}</span> ${value.toFixed(3)}</span>`;
}

function languageLabel() {
  return state.language === "Arabic" ? "AR" : "EN";
}

function isArabic() {
  return state.language === "Arabic";
}

function tr(english, arabic) {
  return isArabic() ? arabic : english;
}

function venueName() {
  return tr("The Nitro Bar", "ذا نيترو بار");
}

function tableLabel() {
  return tr("Table 12", "الطاولة 12");
}

function dineInLabel() {
  return tr("Dine in", "داخل المقهى");
}

function tableContext(includeOpen = false) {
  const parts = [tableLabel(), dineInLabel()];
  if (includeOpen) parts.push(tr("Open now", "مفتوح الآن"));
  return parts.join(" · ");
}

const categoryTranslations = {
  All: "الكل",
  "Coffee Classics": "كلاسيكيات القهوة",
  "Signature Lattes": "لاتيهات مميزة",
  "Nitro Bar Inspired Signatures": "مشروبات ذا نيترو بار",
  "Cold Coffee": "قهوة باردة",
  "Cold Brew & Nitro": "كولد برو ونيترو",
  "Matcha Classics": "كلاسيكيات الماتشا",
  "Signature Matcha": "ماتشا مميزة",
  "Refreshers & Lemonades": "منعشات وليمونادة",
  "Seasonal Specials": "عروض موسمية",
};

const subtitleTranslations = {
  "Ceremonial matcha blend": "خلطة ماتشا فاخرة",
  "Bright fruit refresher": "مشروب فواكه منعش",
  "Slow-steeped coffee base": "قهوة منقوعة ببطء",
  "Served cold over ice": "تقدم باردة مع الثلج",
  "Hopresso crafted signature": "توقيع خاص من ذا نيترو بار",
  "Chocolate espresso blend": "مزيج شوكولاتة وإسبريسو",
  "Hopresso coffee classic": "كلاسيكية قهوة من ذا نيترو بار",
};

function categoryLabel(category) {
  return tr(category, categoryTranslations[category] || category);
}

function productSubtitleLabel(item) {
  return tr(item.subtitle, subtitleTranslations[item.subtitle] || item.subtitle);
}

function productDescriptionLabel(item) {
  return tr(
    item.description,
    `${item.name} من قائمة ${categoryLabel(item.category)}، محضر لطلبك عبر رمز QR.`,
  );
}

function sizeLabel(size) {
  if (size === "Regular") return tr("Regular", "عادي");
  if (size.startsWith("Large")) return tr(size, size.replace("Large", "كبير"));
  return size;
}

function milkLabel(option) {
  const labels = {
    "Standard milk": "حليب عادي",
    "Oat milk": "حليب الشوفان",
    "Extra shot": "شوت إضافي",
  };
  return tr(option, labels[option] || option);
}

function paymentMethodLabel(method = state.paymentMethod) {
  const labels = {
    "Pay at counter": "الدفع عند الكاونتر",
    "Online payment": "الدفع الإلكتروني",
  };
  return tr(method, labels[method] || method);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function applyEnglishNumerals(root = document.querySelector("#app")) {
  if (!isArabic() || !root) return;
  const numberPattern = /([+-]?\d+(?:[.:,]\d+)*%?)/g;
  const textNodes = [];
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
  while (walker.nextNode()) {
    const node = walker.currentNode;
    const parent = node.parentElement;
    if (!parent || parent.closest("script, style, .latin-number")) continue;
    if (numberPattern.test(node.nodeValue)) textNodes.push(node);
    numberPattern.lastIndex = 0;
  }

  textNodes.forEach((node) => {
    const fragment = document.createDocumentFragment();
    node.nodeValue.split(numberPattern).forEach((part, index) => {
      if (!part) return;
      if (index % 2) {
        const span = document.createElement("span");
        span.className = "latin-number";
        span.lang = "en";
        span.dir = "ltr";
        span.textContent = part;
        fragment.appendChild(span);
      } else {
        fragment.appendChild(document.createTextNode(part));
      }
    });
    node.replaceWith(fragment);
  });

  root.querySelectorAll("input").forEach((input) => {
    const value = `${input.value || ""}${input.placeholder || ""}`;
    if (/^[+\d\s().-]+$/.test(value.trim())) {
      input.lang = "en";
      input.dir = "ltr";
    }
  });
}

function product(id = state.selectedProductId) {
  return products.find((item) => item.id === id) || products[0];
}

function cartTotal() {
  return state.cart.reduce((sum, line) => sum + product(line.id).price * line.qty, 0);
}

function serviceFee() {
  return cartTotal() > 0 ? 0.2 : 0;
}

function vatTotal() {
  return (cartTotal() - voucherDiscount() + serviceFee()) * 0.05;
}

function voucherDiscount() {
  return state.voucherApplied ? Math.min(0.1, cartTotal()) : 0;
}

function orderGrandTotal() {
  return cartTotal() - voucherDiscount() + serviceFee() + vatTotal();
}

function cartCount() {
  return state.cart.reduce((sum, line) => sum + line.qty, 0);
}

function routeInfo(route = state.route) {
  return screens.find((screen) => screen.route === route) || screens[14];
}

function go(route, options = {}) {
  state.route = route;
  if (options.productId) state.selectedProductId = options.productId;
  if (options.mode) state.orderMode = options.mode;
  window.location.hash = `/${route}`;
  render();
  window.scrollTo({ top: 0, left: 0 });
}

function setHashRoute() {
  const route = window.location.hash.replace(/^#\/?/, "");
  if (screens.some((screen) => screen.route === route)) state.route = route;
}

function ensureDemoState(route) {
  const needsOrderContext =
    route === "order-cart" ||
    route.startsWith("checkout") ||
    route.startsWith("pickup-") ||
    route.startsWith("delivery-") ||
    route === "payment" ||
    route === "voucher" ||
    route === "success" ||
    route === "unsuccess";

  if (needsOrderContext && state.cart.length === 0) {
    state.cart = [{ id: "americano", qty: 1 }];
  }
}

function icon(name) {
  const paths = {
    home: '<path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"/>',
    cart: '<path d="M6 6h15l-1.6 8.4A2 2 0 0 1 17.5 16H9a2 2 0 0 1-2-1.6L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>',
    scan: '<path d="M4 8V5a1 1 0 0 1 1-1h3m8 0h3a1 1 0 0 1 1 1v3M4 16v3a1 1 0 0 0 1 1h3m8 0h3a1 1 0 0 0 1-1v-3M9 9h6v6H9z"/>',
    history: '<circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/>',
    user: '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0"/>',
    bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/>',
    back: '<path d="M19 12H5"/><path d="m12 5-7 7 7 7"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 1 0-7.8 7.8l8.8 8.7 8.8-8.7a5.5 5.5 0 0 0 0-7.8Z"/>',
    pickup: '<path d="m8 8 2.2 12h3.6L16 8"/><path d="M7 8h10"/><path d="M10 8 12 3h6"/><path d="M10 13h5"/>',
    delivery: '<circle cx="12" cy="11" r="4"/><path d="M12 3v3M12 16v5M5 7l3 2M19 7l-3 2M7 19h10M4 12h4M16 12h4"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    close: '<path d="m6 6 12 12M18 6 6 18"/>',
    star: '<path d="m12 3 2.8 5.7 6.3.9-4.6 4.5 1.1 6.3L12 17.5 6.4 20.4l1.1-6.3L2.9 9.6l6.3-.9L12 3Z"/>',
    share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 5.8-3M8.6 13.5l5.8 3"/>',
    card: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>',
    location: '<path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
    lock: '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    eye: '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
  };
  return `<svg viewBox="0 0 24 24" aria-hidden="true">${paths[name] || paths.home}</svg>`;
}

function statusBar() {
  return `
    <div class="status-bar">
      <strong>9:41</strong>
      <span class="status-icons">
        <svg viewBox="0 0 24 24"><path d="M4 20h2v-5H4v5Zm4 0h2v-8H8v8Zm4 0h2V9h-2v11Zm4 0h2V6h-2v14Z"/></svg>
        <svg viewBox="0 0 24 24"><path d="M3 8.8c5.8-5 12.2-5 18 0M7 13c3.3-2.6 6.7-2.6 10 0m-6 4.2a2 2 0 0 1 2 0"/></svg>
        <span class="battery"></span>
      </span>
    </div>`;
}

function homeIndicator(offset = "") {
  return `<div class="home-indicator${offset}"></div>`;
}

function header({ title, subtitle, back = false, bell = false, search = false, compact = false }) {
  return `
    <header class="top-panel${compact ? " compact" : ""}">
      ${statusBar()}
      <div class="top-content">
        ${back ? `<button class="square white" data-route="${back === true ? "home" : back}">${icon("back")}</button>` : ""}
        <div class="top-copy">
          <h1>${title}</h1>
          ${subtitle ? `<p>${subtitle}</p>` : ""}
        </div>
        ${bell ? `<button class="square white" data-route="notifications">${icon("bell")}</button>` : ""}
      </div>
      ${search ? `<label class="header-search">${icon("search")}<input placeholder="${search}" value="${state.search}" data-search /></label>` : ""}
    </header>`;
}

function bottomNav(active = "home") {
  const items = [
    ["home", "home", "Home"],
    ["order", "cart", "Order"],
    ["scan", "scan", "Scan"],
    ["history", "history", "History"],
    ["profile", "user", "Profile"],
  ];
  return `
    <nav class="bottom-nav">
      ${items
        .map(
          ([route, ico, label]) => `
            <button data-route="${route}" class="${active === route ? "active" : ""}" aria-label="${label}">
              ${icon(ico)}
            </button>`,
        )
        .join("")}
    </nav>`;
}

function primary(label, route, attrs = "") {
  return `<button class="primary-btn" data-route="${route}" ${attrs}>${label}</button>`;
}

function outline(label, route) {
  return `<button class="outline-btn" data-route="${route}">${label}</button>`;
}

function input(label, placeholder, value = "", type = "text", error = "") {
  return `
    <label class="field">
      <span>${label}</span>
      <input type="${type}" placeholder="${placeholder}" value="${value}" />
      ${error ? `<small>${error}</small>` : ""}
    </label>`;
}

function authField(label, value = "", iconName = "user", type = "text", error = "", reveal = false) {
  return `
    <label class="auth-field${error ? " error" : ""}">
      ${icon(iconName)}
      <input type="${type}" aria-label="${label}" placeholder="${label}" value="${value}" />
      ${reveal ? `<span class="auth-eye">${icon("eye")}</span>` : ""}
      ${error ? `<small>${error}</small>` : ""}
    </label>`;
}

function poweredByBite() {
  return `
    <footer class="powered-by-bite" aria-label="Powered by Bite">
      <span>${tr("Powered by", "مشغل بواسطة")}</span>
      <img src="${BRAND_ASSET}/bite-powered-logo.png" alt="Bite" />
    </footer>`;
}

function screenShell(route, body, options = {}) {
  const info = routeInfo(route);
  const tall = options.tall ? ` style="min-height:${options.tall}px"` : "";
  const languageAttrs = ` lang="${isArabic() ? "ar" : "en"}" dir="${isArabic() ? "rtl" : "ltr"}"`;
  const showPoweredBy = options.poweredBy !== false && (options.className || "").includes("web-screen");
  return `
    <section class="screen ${options.className || ""}" data-route-name="${route}" data-figma-screen="${info.number}"${languageAttrs}${tall}>
      ${body}
      ${options.nav ? bottomNav(options.nav) : ""}
      ${showPoweredBy ? poweredByBite() : ""}
      ${options.indicator === false ? "" : homeIndicator(options.tall ? " scroll" : "")}
    </section>`;
}

function splash() {
  return screenShell(
    "splash",
    `
      ${statusBar()}
      <img class="splash-logo" src="${ASSET}/hopresso-logo-white.png" alt="Hopresso" />
      <button class="screen-hotspot" data-route="language" aria-label="Start"></button>
    `,
    { className: "splash-screen" },
  );
}

function onboarding(index) {
  const routes = ["onboarding-1", "onboarding-2", "onboarding-3"];
  const copy = [
    ["Pick Based on Your Mood", "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod."],
    ["Skip the Line, Skip the Hassle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod."],
    ["Enjoy Loyalty Rewards", "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod."],
  ][index - 1];
  const next = index === 3 ? "language" : routes[index];
  return screenShell(
    routes[index - 1],
    `
      ${statusBar()}
      <div class="onboarding-bg onboarding-bg-${index}" aria-hidden="true"></div>
      <div class="onboarding-fader"></div>
      <img class="onboarding-logo" src="${ASSET}/hopresso-logo-white.png" alt="Hopresso" />
      <section class="onboarding-copy">
        <h1>${copy[0]}</h1>
        <p>${copy[1]}</p>
        <div class="dots">${[1, 2, 3].map((dot) => `<span class="${dot === index ? "active" : ""}"></span>`).join("")}</div>
        ${primary(index === 3 ? "LOGIN" : "NEXT", next)}
        <button class="text-link" data-route="language">Skip This Step</button>
      </section>
    `,
    { className: "onboarding-screen" },
  );
}

function authScreen(route) {
  const isRegister = route.startsWith("register");
  const isError = route.includes("error");
  const isFilled = route.includes("filled") || isError;
  const isLogin = route === "login" || route === "login-filled" || route === "login-error";
  const title = route === "forgot-email" ? "Forgot Password" : route === "forgot-password" ? "Create New Password" : isRegister ? "Create your account!" : "Hi, Welcome back !";
  const subtitle = route === "forgot-email" ? "Enter your email to receive verification." : route === "forgot-password" ? "Create a secure password for your account." : "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod.";
  let fields = "";
  if (route === "verification" || route === "verification-filled") {
    fields = authField("Verification Code", route.endsWith("filled") ? "123456" : "", "check");
  } else if (route === "forgot-password") {
    fields = authField("Password", "********", "lock", "password", "", true) + authField("Confirm Password", "********", "lock", "password", "", true);
  } else {
    if (isRegister) fields += authField("Username", isFilled ? "Leslie Alexander" : "", "user");
    fields += authField("Email", isFilled ? "leslie@example.com" : "", "mail", "email", isError ? "Email or password is incorrect" : "");
    if (route !== "forgot-email") fields += authField("Password", isFilled ? "********" : "", "lock", "password", "", true);
    if (isRegister) fields += authField("Confirm Password", isFilled ? "********" : "", "lock", "password", isError ? "Password does not match" : "", true);
  }
  const actionRoute =
    route === "forgot-email"
      ? "verification"
      : route === "forgot-password"
        ? "login"
        : route === "verification" || route === "verification-filled"
          ? "forgot-password"
          : isRegister
            ? "verification"
            : "home";
  return screenShell(
    route,
    `
      <header class="auth-hero">
        ${statusBar()}
        <img class="auth-hero-bg" src="${ASSET}/olive-bg.png" alt="" />
        <button class="square auth-back" data-route="${route === "login" ? "onboarding-3" : "login"}">${icon("back")}</button>
        <div class="auth-title">
          <h1>${title}</h1>
          <p>${subtitle}</p>
        </div>
        <div class="auth-drink ${isRegister ? "register" : "login"}" aria-hidden="true"></div>
      </header>
      <section class="auth-card ${isRegister ? "register" : ""}">
        <h1>${title}</h1>
        <p>${subtitle}</p>
        <div class="form-stack">${fields}</div>
        <div class="auth-options">
          <label><input type="checkbox" ${isFilled ? "checked" : ""} /> <span>Remember me</span></label>
          ${isLogin ? `<button data-route="forgot-email">forgot password?</button>` : ""}
        </div>
        ${primary(route === "verification" || route === "verification-filled" ? "VERIFY" : route.includes("forgot") ? "CONTINUE" : "LOGIN", actionRoute)}
        <div class="auth-divider"><span></span><em>Or login with</em><span></span></div>
        <div class="social-row">
          <button aria-label="Google"><img src="${ASSET}/social-google.png" alt="" /></button>
          <button aria-label="Apple"><img src="${ASSET}/social-apple.png" alt="" /></button>
          <button aria-label="Facebook"><img src="${ASSET}/social-facebook.png" alt="" /></button>
        </div>
        <div class="auth-switch">
          <span>Already have an account?</span>
          <button data-route="${isRegister ? "login" : "register"}">${isRegister ? "Login" : "Register"}</button>
        </div>
      </section>
    `,
    { className: "auth-screen" },
  );
}

function guestHome(route = "home-guest-1") {
  const variant = route.endsWith("2") ? "Delivery" : "Pick Up";
  return screenShell(
    route,
    `
      <section class="notification-shell">
        ${statusBar()}
        <div class="notification-content">
          <div>
            <h1>Hallo Everyone!</h1>
            <p>Good morning, have a nice day.</p>
          </div>
          <button class="square white" data-route="login-alert">${icon("bell")}</button>
        </div>
      </section>
      <section class="home-main">
        <section class="login-card">
          <img class="login-cup" src="${ASSET}/cup-togo.png" alt="" /><span class="login-brand">HOPRESSO</span>
          <div class="login-copy"><h2>Log in to <span>Continue!</span></h2><p>Log in and make it happen</p></div>
          <button class="login-pill" data-route="login">Login</button>
          <label class="home-search">${icon("search")}<input placeholder="Look for the nearest outlet" /></label>
        </section>
        <section class="method-panel">
          <button class="${variant === "Pick Up" ? "selected" : ""}" data-route="home-guest-1">${icon("pickup")}<span>Pick Up</span></button>
          <i></i>
          <button class="${variant === "Delivery" ? "selected" : ""}" data-route="home-guest-2">${icon("delivery")}<span>Delivery</span></button>
        </section>
        ${offers()}
        ${recommended()}
      </section>
      ${bottomNav("home")}
    `,
    { className: "home-screen" },
  );
}

function filteredProducts(limit = null) {
  const filtered = products.filter((item) => {
    const matchesCategory = state.selectedCategory === "All" || item.category === state.selectedCategory;
    const text = `${item.name} ${item.subtitle} ${item.category}`.toLowerCase();
    const matchesSearch = !state.search || text.includes(state.search.toLowerCase());
    return matchesCategory && matchesSearch;
  });
  const sorted =
    state.selectedCategory === "All" && !state.search
      ? [...filtered].sort((a, b) => {
          const rankA = featuredProductIds.includes(a.id) ? featuredProductIds.indexOf(a.id) : 999;
          const rankB = featuredProductIds.includes(b.id) ? featuredProductIds.indexOf(b.id) : 999;
          return rankA - rankB;
        })
      : filtered;
  return limit ? sorted.slice(0, limit) : sorted;
}

function homePopularProducts(limit = 4) {
  return featuredProductIds.map((id) => product(id)).filter(Boolean).slice(0, limit);
}

function categoryTabs() {
  return `
    <div class="web-category-row" aria-label="Menu categories">
      ${categories
        .map(
          (category) =>
            `<button class="${state.selectedCategory === category ? "active" : ""}" data-category="${category}">${categoryLabel(category)}</button>`,
        )
        .join("")}
    </div>`;
}

function webStickyCart(label = "View cart") {
  if (!cartCount()) return "";
  return `
    <button class="web-cart-cta" data-route="order-cart">
      <span>${cartCount()} ${cartCount() === 1 ? tr("item", "منتج") : tr("items", "منتجات")}</span>
      <strong>${label} · ${money(orderGrandTotal())}</strong>
    </button>`;
}

function languageGate() {
  return screenShell(
    "language",
    `
      <video class="language-bg-video" autoplay muted loop playsinline preload="auto" poster="${ASSET}/cafe-interior.jpg" aria-hidden="true" tabindex="-1">
        <source src="${ASSET}/language-background.mp4" type="video/mp4" />
        <source src="${ASSET}/language-cafe-motion.webm" type="video/webm" />
      </video>
      <div class="language-bg-overlay" aria-hidden="true"></div>
      ${statusBar()}
      <section class="language-hero">
        <img src="${ASSET}/hopresso-logo-white.png" alt="Hopresso" />
        <div>
          <p>${venueName()} · ${tableLabel()}</p>
          <h1>Choose your language</h1>
          <span lang="ar" dir="rtl">اختر لغتك</span>
        </div>
      </section>
      <section class="language-options">
        <button data-language="English">
          <strong>English</strong>
          <span>Continue to menu</span>
        </button>
        <button data-language="Arabic">
          <strong lang="ar" dir="rtl">العربية</strong>
          <span lang="ar" dir="rtl">المتابعة إلى القائمة</span>
        </button>
      </section>
    `,
    { className: "web-screen language-screen", indicator: false },
  );
}

function loggedHome(route = "home") {
  return screenShell(
    route,
    `
      <section class="web-hero">
        ${statusBar()}
        <div class="web-hero-bg"></div>
        <div class="web-hero-top">
          <img src="${ASSET}/hopresso-logo-white.png" alt="Hopresso" />
          <button data-language-toggle>${languageLabel()}</button>
        </div>
        <div class="web-hero-copy">
          <p>${venueName()}</p>
          <h1>${tr("Order from your table", "اطلب من طاولتك")}</h1>
          <span>${icon("location")} ${tableContext(true)}</span>
        </div>
      </section>
      <section class="web-main">
        <div class="web-context-strip">
          <div>${icon("pickup")}<span>${tr("15 min average", "متوسط 15 دقيقة")}</span></div>
          <div>${icon("card")}<span>${paymentMethodLabel("Pay at counter")}</span></div>
        </div>
        ${offers()}
        <section class="recommended-section">
          <div class="section-title"><h2>${tr("Popular for this table", "الأكثر طلباً لهذه الطاولة")}</h2><button data-route="order">${tr("See all", "عرض الكل")}</button></div>
          <div class="product-grid web-product-grid">
            ${homePopularProducts(4)
              .map(
                (item) => `
                  <article class="product-card">
                    <button class="product-open" data-route="product-detail" data-product="${item.id}">
                      <img src="${item.image}" alt="${item.name}" />
                      <h3>${item.name}</h3>
                      <p>${productSubtitleLabel(item)}</p>
                      <strong>${money(item.price)}</strong>
                    </button>
                    <button class="mini-plus" data-add="${item.id}" aria-label="Add ${item.name}">${icon("plus")}</button>
                  </article>`,
              )
              .join("")}
          </div>
        </section>
      </section>
      ${webStickyCart(tr("View cart", "عرض السلة"))}
    `,
    { className: "web-screen home-screen", indicator: false },
  );
}

function offers() {
  const highlighted = product(highlightedProductId);
  return `
    <section class="offers-section">
      <h2>${tr("Today's Highlight", "اختيار اليوم")}</h2>
      <article class="offer-card highlight-card" data-route="product-detail" data-product="${highlighted.id}">
        <div>
          <span class="highlight-kicker">${tr("Owner's pick", "اختيار المالك")}</span>
          <h3>${highlighted.name}<br /><span>${money(highlighted.price)}</span></h3>
          <p>${productSubtitleLabel(highlighted)}</p>
          <button data-route="product-detail" data-product="${highlighted.id}">${tr("View item →", "← عرض المنتج")}</button>
        </div>
        <img src="${highlighted.image}" alt="${highlighted.name}" />
      </article>
    </section>`;
}

function recommended() {
  return `
    <section class="recommended-section">
      <div class="section-title"><h2>${tr("Recommended Menu", "قائمة مقترحة")}</h2><button data-route="order">${tr("see more", "عرض المزيد")} →</button></div>
      <div class="product-grid">
        ${products
          .slice(0, 2)
          .map(
            (item) => `
              <article class="product-card">
                <button class="heart">${icon("heart")}</button>
                <button class="product-open" data-route="product-detail" data-product="${item.id}">
                  <img src="${item.image}" alt="${item.name}" />
                  <h3>${item.name}</h3>
                  <p>${productSubtitleLabel(item)}</p>
                  <strong>${money(item.price)}</strong>
                </button>
                <button class="mini-plus" data-add="${item.id}">${icon("plus")}</button>
              </article>`,
          )
          .join("")}
      </div>
    </section>`;
}

function loginAlert() {
  return screenShell(
    "login-alert",
    `
      <section class="notification-shell">
        ${statusBar()}
        <div class="notification-content">
          <div>
            <h1>Hallo Everyone!</h1>
            <p>Good morning, have a nice day.</p>
          </div>
          <button class="square white" data-route="home-guest-1">${icon("bell")}</button>
        </div>
      </section>
      <section class="home-main">
        <section class="login-card">
          <img class="login-cup" src="${ASSET}/cup-togo.png" alt="" /><span class="login-brand">HOPRESSO</span>
          <div class="login-copy"><h2>Log in to <span>Continue!</span></h2><p>Log in and make it happen</p></div>
          <button class="login-pill" data-route="login">Login</button>
          <label class="home-search">${icon("search")}<input placeholder="Look for the nearest outlet" /></label>
        </section>
        <section class="method-panel">
          <button class="selected" data-route="home-guest-1">${icon("pickup")}<span>Pick Up</span></button>
          <i></i>
          <button data-route="home-guest-2">${icon("delivery")}<span>Delivery</span></button>
        </section>
        ${offers()}
        ${recommended()}
      </section>
      ${bottomNav("home")}
      <div class="modal-backdrop">
        <section class="alert-modal">
          <button class="square ghost" data-route="home-guest-1">${icon("close")}</button>
          <img src="${ASSET}/cup-togo.png" alt="" />
          <h2>Login Required</h2>
          <p>Please login first to save orders, vouchers, and favorite outlets.</p>
          ${primary("Login", "login")}
          ${outline("Continue as Guest", "home-guest-1")}
        </section>
      </div>`,
    { className: "modal-screen" },
  );
}

function menuRow(item) {
  return `
    <article class="menu-row">
      <button data-route="product-detail" data-product="${item.id}">
        <img src="${item.image}" alt="${item.name}" />
        <div><h3>${item.name}</h3><p>${productSubtitleLabel(item)}</p><strong>${money(item.price)}</strong></div>
      </button>
      <button class="mini-plus" data-add="${item.id}">${icon("plus")}</button>
    </article>`;
}

function emptyMenuState() {
  return `<div class="empty-state compact"><img src="${ASSET}/beans-bag.png" alt="" /><h2>${tr("No items found", "لم يتم العثور على منتجات")}</h2><p>${tr("Try another category or search.", "جرّب تصنيفاً آخر أو ابحث باسم مختلف.")}</p></div>`;
}

function listProducts() {
  const filtered = filteredProducts();
  if (!filtered.length) return emptyMenuState();

  if (state.selectedCategory === "All") {
    return menuCatalog
      .map((section) => {
        const sectionProducts = filtered.filter((item) => item.category === section.category);
        if (!sectionProducts.length) return "";
        return `
          <section class="menu-category-section">
            <h2 class="menu-category-title">${categoryLabel(section.category)}</h2>
            <div class="menu-category-items">${sectionProducts.map(menuRow).join("")}</div>
          </section>`;
      })
      .join("");
  }

  return filtered.map(menuRow).join("");
}

function orderScreen(route = "order") {
  return screenShell(
    route,
    `
      <section class="web-subheader">
        ${statusBar()}
        <div class="web-subheader-row">
          <button class="square white" data-route="home">${icon("back")}</button>
          <div><h1>${tr("Full menu", "القائمة الكاملة")}</h1><p>${venueName()} · ${tableLabel()}</p></div>
          <button class="web-lang" data-language-toggle>${languageLabel()}</button>
        </div>
        <label class="web-search">${icon("search")}<input placeholder="${tr("Search menu", "ابحث في القائمة")}" value="${state.search}" data-search /></label>
      </section>
      <section class="web-main order-content">
        ${categoryTabs()}
        <div class="menu-list">${listProducts()}</div>
      </section>
      ${webStickyCart(tr("View cart", "عرض السلة"))}
    `,
    { className: "web-screen order-screen", indicator: false },
  );
}

function searchProduct(empty = false) {
  return screenShell(
    empty ? "search-empty" : "search-product",
    `
      ${header({ title: "Search Product", back: "order", search: "Search product" })}
      <section class="content">
        ${
          empty
            ? `<div class="empty-state"><img src="${ASSET}/beans-bag.png" alt="" /><h2>Product Not Found</h2><p>Try another search or category.</p>${outline("Back to Order", "order")}</div>`
            : `<div class="menu-list">${listProducts()}</div>`
        }
      </section>
    `,
    { className: "order-screen" },
  );
}

function productDetail() {
  const item = product();
  const sizes = ["Regular", "Large +0.400"];
  const milkOptions = ["Standard milk", "Oat milk", "Extra shot"];
  return screenShell(
    "product-detail",
    `
      <section class="product-hero">
        ${statusBar()}
        <button class="square white back-over" data-route="home">${icon("back")}</button>
        <img src="${item.image}" alt="${item.name}" />
      </section>
      <section class="detail-content">
        <div class="title-price"><div><h1>${item.name}</h1><p>${productSubtitleLabel(item)}</p></div><strong>${money(item.price)}</strong></div>
        <div class="metric-row"><span>${item.rating} ${tr("Rating", "تقييم")}</span><span>${item.calories}</span><span>${tr("15 min", "15 دقيقة")}</span></div>
        <p class="description">${productDescriptionLabel(item)}</p>
        <h2>${tr("Size", "الحجم")}</h2>
        <div class="choice-row">
          ${sizes.map((size) => `<button class="${state.selectedSize === size ? "active" : ""}" data-size="${size}">${sizeLabel(size)}</button>`).join("")}
        </div>
        <h2>${tr("Milk & add-ons", "الحليب والإضافات")}</h2>
        <div class="choice-row">
          ${milkOptions.map((option) => `<button class="${state.selectedMilk === option ? "active" : ""}" data-milk="${option}">${milkLabel(option)}</button>`).join("")}
        </div>
        <label class="web-note detail-note">
          <span>${tr("Item note", "ملاحظة على المنتج")}</span>
          <textarea rows="3" placeholder="${tr("Less ice, no sugar, allergy note...", "ثلج أقل، بدون سكر، ملاحظة حساسية...")}"></textarea>
        </label>
      </section>
      <button class="checkout-bottom" data-add="${item.id}" data-route-after-add="order-cart">${tr("Add to Cart", "أضف إلى السلة")} - ${money(item.price)}</button>
    `,
    { className: "web-screen detail-screen", tall: 1167, indicator: false },
  );
}

function cartPanel() {
  const lines = state.cart.length ? state.cart : [{ id: "americano", qty: 1 }];
  return `
    <aside class="cart-panel">
      <div class="section-title"><h2>Your Order</h2><button data-route="order">Edit</button></div>
      ${lines
        .map((line) => {
          const item = product(line.id);
          return `<div class="cart-line"><img src="${item.image}" alt="" /><div><h3>${item.name}</h3><p>Regular</p><strong>${money(item.price * line.qty)}</strong></div><span>x${line.qty}</span></div>`;
        })
        .join("")}
      <div class="summary"><span>Subtotal</span><strong>${money(cartTotal() || 4)}</strong></div>
      <div class="summary total"><span>Total</span><strong>${money(cartTotal() || 4)}</strong></div>
      ${primary("Checkout", state.orderMode === "Delivery" ? "checkout-delivery" : "checkout-pickup")}
    </aside>`;
}

function cartPage() {
  const lines = state.cart.length ? state.cart : [];
  return screenShell(
    "order-cart",
    `
      <section class="web-subheader">
        ${statusBar()}
        <div class="web-subheader-row">
          <button class="square white" data-route="home">${icon("back")}</button>
          <div><h1>${tr("Your cart", "سلتك")}</h1><p>${tableContext()}</p></div>
          <button class="web-lang" data-language-toggle>${languageLabel()}</button>
        </div>
      </section>
      <section class="web-main web-cart-page">
        ${
          lines.length
            ? lines
                .map((line) => {
                  const item = product(line.id);
                  return `
                    <article class="web-cart-line">
                      <img src="${item.image}" alt="${item.name}" />
                      <div>
                        <h3>${item.name}</h3>
                        <p>${sizeLabel(state.selectedSize)} · ${milkLabel(state.selectedMilk)}</p>
                        <strong>${money(item.price * line.qty)}</strong>
                      </div>
                      <div class="qty-stepper">
                        <button data-cart-dec="${item.id}" aria-label="${tr("Remove one", "إزالة واحد")} ${item.name}">-</button>
                        <span>${line.qty}</span>
                        <button data-cart-inc="${item.id}" aria-label="${tr("Add one", "إضافة واحد")} ${item.name}">+</button>
                      </div>
                    </article>`;
                })
                .join("")
            : `<div class="empty-state compact"><img src="${ASSET}/cup-togo.png" alt="" /><h2>${tr("Your cart is empty", "سلتك فارغة")}</h2><p>${tr("Add something from the menu to start an order.", "أضف منتجاً من القائمة لبدء الطلب.")}</p>${outline(tr("Back to menu", "العودة للقائمة"), "home")}</div>`
        }
        <label class="web-note">
          <span>${tr("Order note", "ملاحظة الطلب")}</span>
          <textarea rows="3" placeholder="${tr("No sugar, extra hot, allergies...", "بدون سكر، ساخن جداً، حساسية...")}"></textarea>
        </label>
        <section class="web-summary">
          <div><span>${tr("Subtotal", "المجموع الفرعي")}</span><strong>${money(cartTotal())}</strong></div>
          <div><span>${tr("Service", "الخدمة")}</span><strong>${money(serviceFee())}</strong></div>
          <div><span>${tr("VAT 5%", "ضريبة 5%")}</span><strong>${money(vatTotal())}</strong></div>
          <div class="total"><span>${tr("Total", "الإجمالي")}</span><strong>${money(orderGrandTotal())}</strong></div>
        </section>
      </section>
      ${lines.length ? `<button class="checkout-bottom web-bottom-action" data-route="checkout-pickup">${tr("Checkout", "إتمام الطلب")}</button>` : ""}
    `,
    { className: "web-screen cart-screen", indicator: false },
  );
}

function checkout(route) {
  const complete = route.includes("complete");
  return screenShell(
    route,
    `
      <section class="web-subheader">
        ${statusBar()}
        <div class="web-subheader-row">
          <button class="square white" data-route="order-cart">${icon("back")}</button>
          <div><h1>${tr("Checkout", "إتمام الطلب")}</h1><p>${tableContext()}</p></div>
          <button class="web-lang" data-language-toggle>${languageLabel()}</button>
        </div>
      </section>
      <section class="web-main checkout-content">
        <section class="checkout-section">
          <h2>${tr("Table", "الطاولة")}</h2>
          <div class="select-card">${icon("location")}<div><strong>${venueName()}</strong><p>${tableContext()}</p></div></div>
        </section>
        <section class="checkout-section">
          <h2>${tr("Contact details", "بيانات التواصل")}</h2>
          ${input(tr("Name", "الاسم"), tr("Your name", "اسمك"), complete ? state.user.name : "")}
          ${input(tr("Phone", "رقم الهاتف"), "+968", complete ? "+968 9123 4567" : "")}
        </section>
        <section class="checkout-section">
          <h2>${tr("Payment", "الدفع")}</h2>
          <div class="payment-dropdown ${state.paymentOpen ? "open" : ""}">
            <button class="payment-trigger" data-payment-toggle aria-expanded="${state.paymentOpen ? "true" : "false"}">
              ${icon("card")}
              <span>${tr("Payment method", "طريقة الدفع")}</span>
              <strong>${paymentMethodLabel()}</strong>
              <b aria-hidden="true"></b>
            </button>
            ${
              state.paymentOpen
                ? `<div class="payment-menu">
                    ${paymentMethods
                      .map(
                        (method) => `
                          <button class="${state.paymentMethod === method ? "active" : ""}" data-payment-option="${method}">
                            <span>${paymentMethodLabel(method)}</span>
                            ${state.paymentMethod === method ? icon("check") : ""}
                          </button>`,
                      )
                      .join("")}
                  </div>`
                : ""
            }
          </div>
        </section>
        <section class="checkout-section">
          <h2>${tr("Voucher", "القسيمة")}</h2>
          <div class="voucher-field ${state.voucherApplied ? "applied" : ""}">
            ${icon("heart")}
            <label>
              <span>${tr("Promo code", "رمز الخصم")}</span>
              <input data-voucher-input value="${escapeHtml(state.voucherCode)}" placeholder="${tr("Enter voucher code", "أدخل رمز الخصم")}" />
            </label>
            <button data-voucher-apply>${state.voucherApplied ? tr("Applied", "تم التطبيق") : tr("Apply", "تطبيق")}</button>
          </div>
          ${state.voucherApplied ? `<p class="voucher-feedback">${escapeHtml(state.voucherCode || tr("Voucher", "القسيمة"))} ${tr("applied", "تم تطبيقها")} · ${money(voucherDiscount())} ${tr("off", "خصم")}</p>` : ""}
        </section>
        <section class="web-summary">
          <div><span>${tr("Subtotal", "المجموع الفرعي")}</span><strong>${money(cartTotal())}</strong></div>
          ${voucherDiscount() ? `<div><span>${tr("Voucher", "القسيمة")}</span><strong>- ${money(voucherDiscount())}</strong></div>` : ""}
          <div><span>${tr("Service", "الخدمة")}</span><strong>${money(serviceFee())}</strong></div>
          <div><span>${tr("VAT 5%", "ضريبة 5%")}</span><strong>${money(vatTotal())}</strong></div>
          <div class="total"><span>${tr("Total", "الإجمالي")}</span><strong>${money(orderGrandTotal())}</strong></div>
        </section>
      </section>
      <button class="checkout-bottom web-bottom-action" data-route="pickup-placed">${tr("Place order", "إرسال الطلب")}</button>
    `,
    { className: "web-screen checkout-screen", indicator: false },
  );
}

function orderStatus(route) {
  const labels = [
    tr("Order received", "تم استلام الطلب"),
    tr("Accepted", "تم القبول"),
    tr("Preparing", "قيد التحضير"),
    tr("Ready at table", "جاهز للطاولة"),
  ];
  const routeMap = ["pickup-placed", "pickup-accepted", "pickup-progress", "pickup-ready"];
  const active = Math.max(0, routeMap.indexOf(route));
  const next = routeMap[Math.min(routeMap.length - 1, active + 1)];
  return screenShell(
    route,
    `
      <section class="web-subheader">
        ${statusBar()}
        <div class="web-subheader-row">
          <button class="square white" data-route="home">${icon("back")}</button>
          <div><h1>${labels[active]}</h1><p>${tr("Order", "طلب")} #HP-2048 · ${tableLabel()}</p></div>
          <button class="web-lang" data-language-toggle>${languageLabel()}</button>
        </div>
      </section>
      <section class="status-visual">
        <img src="${ASSET}/cup-togo.png" alt="" />
      </section>
      <section class="status-card web-status-card">
        <h2>${labels[active]}</h2>
        <p>${tr("Your order was sent to", "تم إرسال طلبك إلى")} ${venueName()}. ${tr("Keep this page open or scan the table QR again to come back.", "اترك هذه الصفحة مفتوحة أو امسح رمز الطاولة مرة أخرى للعودة.")}</p>
        <div class="timeline">
          ${labels.map((label, idx) => `<div class="${idx <= active ? "done" : ""}"><span>${idx < active ? icon("check") : idx + 1}</span><p>${label}</p></div>`).join("")}
        </div>
        <div class="status-actions">
          ${primary(active === routeMap.length - 1 ? tr("Order more", "اطلب المزيد") : tr("Simulate next status", "محاكاة الحالة التالية"), active === routeMap.length - 1 ? "home" : next)}
          ${outline(tr("Rate your visit", "قيّم زيارتك"), "app-invite")}
        </div>
      </section>
    `,
    { className: "web-screen status-screen", indicator: false },
  );
}

function orderDetail(route) {
  const delivery = route === "delivery-detail";
  return screenShell(
    route,
    `
      ${header({ title: "Order Detail", subtitle: delivery ? "Delivery" : "Pick Up", back: "history" })}
      <section class="content">
        <div class="receipt-card">
          <h2>Order #HP-2048</h2>
          <p>${delivery ? "Delivered to Royal Opera House area" : "Ready at Outlet 1"}</p>
          ${cartPanel()}
          <div class="summary total"><span>Payment</span><strong>${state.paymentMethod}</strong></div>
          ${primary("Order Again", "order")}
        </div>
      </section>
    `,
    { className: "detail-list-screen", tall: delivery ? 1202 : 1104 },
  );
}

function voucher() {
  return screenShell(
    "voucher",
    `
      <section class="web-subheader">
        ${statusBar()}
        <div class="web-subheader-row">
          <button class="square white" data-route="checkout-pickup">${icon("back")}</button>
          <div><h1>${tr("Voucher", "القسيمة")}</h1><p>${tr("Optional discount", "خصم اختياري")}</p></div>
          <button class="web-lang" data-language-toggle>${languageLabel()}</button>
        </div>
      </section>
      <section class="web-main voucher-list">
        ${["NEWFLAVOR20", "PICKUP10", "LATTELOVE"].map((code, idx) => `<button class="voucher-card" data-route="checkout-pickup-complete"><strong>${code}</strong><span>${idx + 10}% ${tr("off selected menu", "خصم على منتجات مختارة")}</span></button>`).join("")}
      </section>
    `,
    { className: "web-screen voucher-screen", indicator: false },
  );
}

function payment() {
  return screenShell(
    "payment",
    `
      <section class="web-subheader">
        ${statusBar()}
        <div class="web-subheader-row">
          <button class="square white" data-route="checkout-pickup">${icon("back")}</button>
          <div><h1>${tr("Payment", "الدفع")}</h1><p>${tr("Checkout", "إتمام الطلب")}</p></div>
          <button class="web-lang" data-language-toggle>${languageLabel()}</button>
        </div>
      </section>
      <section class="web-main">
        ${paymentMethods.map((method) => `<button class="select-card payment-choice ${state.paymentMethod === method ? "active" : ""}" data-payment="${method}">${icon("card")}<div><strong>${paymentMethodLabel(method)}</strong><p>${method === "Online payment" ? tr("Mock option for prototype", "خيار تجريبي للنموذج") : tr("Pay when the order is served", "ادفع عند تقديم الطلب")}</p></div></button>`).join("")}
      </section>
    `,
    { className: "web-screen payment-screen", indicator: false },
  );
}

function result(success = true) {
  return screenShell(
    success ? "success" : "unsuccess",
    `
      ${statusBar()}
      <section class="result-content">
        <div class="result-icon ${success ? "ok" : "fail"}">${icon(success ? "check" : "x")}</div>
        <h1>${success ? "Payment Success!" : "Payment Failed"}</h1>
        <p>${success ? "Your order has been placed successfully." : "Please try another payment method."}</p>
        <div class="order-number">HP-2048</div>
        ${primary(success ? "Track Order" : "Try Again", success ? (state.orderMode === "Delivery" ? "delivery-placed" : "pickup-placed") : "payment")}
      </section>
    `,
    { className: "result-screen" },
  );
}

function appInvite() {
  const googleReviewUrl = "https://www.google.com/maps/search/?api=1&query=The%20Nitro%20Bar%20Qurum";
  const instagramUrl = "https://www.instagram.com/explore/search/keyword/?q=The%20Nitro%20Bar";
  return screenShell(
    "app-invite",
    `
      ${statusBar()}
      <section class="app-invite-hero">
        <div>
          <p>${tr("Before you go", "قبل أن تغادر")}</p>
          <h1>${tr("How was your visit to The Nitro Bar?", "كيف كانت زيارتك لذا نيترو بار؟")}</h1>
          <span>${tr("Leave a quick rating or follow the cafe for new drinks and table offers.", "اترك تقييماً سريعاً أو تابع المقهى لمعرفة المشروبات والعروض الجديدة.")}</span>
        </div>
      </section>
      <section class="rating-panel">
        <div class="rating-stars" aria-label="${tr("Five star rating", "تقييم خمس نجوم")}">
          ${Array.from({ length: 5 }, () => icon("star")).join("")}
        </div>
        <a class="review-action primary-review" href="${googleReviewUrl}" target="_blank" rel="noopener noreferrer">
          ${icon("location")}
          <span>
            <strong>${tr("Rate on Google Maps", "قيّم على خرائط Google")}</strong>
            <small>${tr("Opens the cafe listing", "يفتح صفحة المقهى")}</small>
          </span>
        </a>
        <a class="review-action" href="${instagramUrl}" target="_blank" rel="noopener noreferrer">
          ${icon("share")}
          <span>
            <strong>${tr("Follow on Instagram", "تابعنا على Instagram")}</strong>
            <small>${tr("Cafe updates and specials", "تحديثات وعروض المقهى")}</small>
          </span>
        </a>
      </section>
      <section class="app-invite-actions">
        ${primary(tr("Back to menu", "العودة للقائمة"), "home")}
        ${outline(tr("Track current order", "تتبع الطلب الحالي"), "pickup-placed")}
      </section>
    `,
    { className: "web-screen app-invite-screen", indicator: false },
  );
}

function searchOutlet() {
  return screenShell(
    "search-outlet",
    `
      ${header({ title: "Search Outlet", back: "home", search: "Look for the nearest outlet" })}
      <section class="content outlet-list">
        ${outlets.map((outlet) => `<button class="outlet-row" data-route="detail-outlet"><img src="${outlet.image}" alt="" /><div><h3>${outlet.name}</h3><p>${outlet.place}</p><span>${outlet.distance}</span></div></button>`).join("")}
      </section>
    `,
    { className: "outlet-screen" },
  );
}

function detailOutlet() {
  return screenShell(
    "detail-outlet",
    `
      <section class="outlet-hero">
        ${statusBar()}
        <button class="square white back-over" data-route="home">${icon("back")}</button>
        <img src="${ASSET}/cafe-interior.jpg" alt="" />
      </section>
      <section class="content outlet-detail">
        <h1>Outlet 1</h1>
        <p>Muscat, Oman</p>
        <div class="metric-row"><span>4.8 Rating</span><span>1.2 km</span><span>Open</span></div>
        <p class="description">A calm Hopresso outlet with pickup and delivery service available all day.</p>
        ${primary("Start Order", "order")}
      </section>
    `,
    { className: "outlet-detail-screen" },
  );
}

function notificationsScreen() {
  return screenShell(
    "notifications",
    `
      ${header({ title: "Notification", back: "home" })}
      <section class="content">
        ${notifications.map(([title, body, time]) => `<article class="notice"><span>${icon("bell")}</span><div><h3>${title}</h3><p>${body}</p><small>${time}</small></div></article>`).join("")}
      </section>
    `,
    { className: "notifications-screen" },
  );
}

function scanScreen(route = "scan") {
  const flash = route === "scan-flash";
  return screenShell(
    route,
    `
      ${statusBar()}
      <button class="square white back-over" data-route="home">${icon("back")}</button>
      <div class="scan-stage">
        <div class="scan-frame ${flash ? "flash" : ""}"><span></span><span></span><span></span><span></span></div>
        <p>Align QR code inside the frame</p>
      </div>
      <button class="flash-btn ${flash ? "active" : ""}" data-route="${flash ? "scan" : "scan-flash"}">${flash ? "Flash On" : "Flash"}</button>
    `,
    { nav: "scan", className: "scan-screen" },
  );
}

function myQr() {
  return screenShell(
    "my-qr",
    `
      ${header({ title: "My QR Code", back: "profile" })}
      <section class="content center-content">
        <div class="qr-box"><img src="${ASSET}/menu-qr.png" alt="QR code" /></div>
        <div class="alert-row">Show this code to scan your member profile.</div>
      </section>
    `,
    { nav: "scan", className: "qr-screen" },
  );
}

function history() {
  return screenShell(
    "history",
    `
      ${header({ title: "History", back: "home", search: "Search history" })}
      <section class="content">
        ${["pickup-ready", "delivery-delivered", "pickup-progress"].map((route, idx) => `<button class="history-row" data-route="${route}"><img src="${products[idx].image}" alt="" /><div><h3>${idx === 1 ? "Delivery Order" : "Pick Up Order"}</h3><p>${products[idx].name} and ${idx + 1} more item</p><strong>${money(products[idx].price + 4)}</strong></div><span>${idx === 2 ? "In progress" : "Complete"}</span></button>`).join("")}
      </section>
    `,
    { nav: "history", className: "history-screen" },
  );
}

function favorite() {
  return screenShell(
    "favorite",
    `
      ${header({ title: "Favorite", back: "home", search: "Search favorite" })}
      <section class="content">
        ${products.map((item) => `<article class="menu-row favorite-row"><button data-route="product-detail" data-product="${item.id}"><img src="${item.image}" alt="" /><div><h3>${item.name}</h3><p>${item.subtitle}</p><strong>${money(item.price)}</strong></div></button><button class="heart active">${icon("heart")}</button></article>`).join("")}
      </section>
    `,
    { nav: "profile", className: "favorite-screen", tall: 1000 },
  );
}

function profile() {
  const rows = [
    ["Edit Profile", "edit-profile", "user"],
    ["Address", "address", "location"],
    ["Card", "card", "card"],
    ["Language", "language", "user"],
    ["Privacy & Policy", "privacy", "card"],
    ["Terms of Service", "terms", "card"],
    ["Help Center", "help", "bell"],
  ];
  return screenShell(
    "profile",
    `
      ${header({ title: "Profile", subtitle: "Manage your account", bell: true })}
      <section class="content profile-content">
        <div class="profile-card"><img src="${ASSET}/cup-togo.png" alt="" /><h2>${state.user.name}</h2><p>${state.user.email}</p></div>
        ${rows.map(([label, route, ico]) => `<button class="profile-row" data-route="${route}">${icon(ico)}<span>${label}</span><b>›</b></button>`).join("")}
      </section>
    `,
    { nav: "profile", className: "profile-screen", tall: 997 },
  );
}

function profileForm(route) {
  const titleMap = {
    "edit-profile": "Edit Profile",
    address: "Adress",
    card: "Card",
    language: "Language",
    privacy: "Privacy & Policy",
    terms: "Termj of Service",
    help: "Help Center",
  };
  let body = "";
  if (route === "edit-profile") {
    body = `${input("Full Name", "Name", state.user.name)}${input("Email", "Email", state.user.email, "email")}${input("Phone", "Phone", state.user.phone)}`;
  } else if (route === "address") {
    body = `<div class="select-card">${icon("location")}<div><strong>Home Address</strong><p>Royal Opera House area, Muscat</p></div></div><div class="select-card">${icon("location")}<div><strong>Office</strong><p>Madinat Al Sultan Qaboos</p></div></div>`;
  } else if (route === "card") {
    body = `<div class="credit-card"><span>Hopresso Card</span><strong>**** 2458</strong><p>Leslie Alexander</p></div>${input("Card Number", "0000 0000 0000 0000", "4242 4242 4242 4242")}`;
  } else if (route === "language") {
    body = `<button class="select-card active"><div><strong>English</strong><p>Current language</p></div></button><button class="select-card"><div><strong>Arabic</strong><p lang="ar" dir="rtl">العربية</p></div></button>`;
  } else if (route === "help") {
    body = ["How do I track my order?", "Can I change delivery address?", "How do vouchers work?"].map((q) => `<details class="faq"><summary>${q}</summary><p>Use the related screen in the prototype to complete this action.</p></details>`).join("");
  } else {
    body = `<article class="legal-copy"><p>Hopresso respects your account, order, and payment information. This prototype uses local mock data only.</p><p>By using the app you agree to the order and pickup policies shown in this design kit.</p></article>`;
  }
  return screenShell(
    route,
    `
      ${header({ title: titleMap[route], back: "profile" })}
      <section class="content form-page">${body}</section>
      ${route === "privacy" || route === "terms" ? primary("I Understand", "profile") : primary("Save", "profile")}
    `,
    { className: "profile-form-screen" },
  );
}

function renderRoute(route) {
  if (route === "splash") return splash();
  if (route.startsWith("onboarding")) return onboarding(Number(route.split("-")[1]));
  if (["login", "login-filled", "login-error", "verification", "verification-filled", "forgot-email", "forgot-password", "register", "register-filled", "register-error"].includes(route)) return authScreen(route);
  if (route === "language") return languageGate();
  if (route.startsWith("home-guest")) return guestHome(route);
  if (route === "login-alert") return loginAlert();
  if (["home", "home-pickup", "home-delivery"].includes(route)) return loggedHome(route);
  if (route === "notifications") return notificationsScreen();
  if (route === "search-outlet") return searchOutlet();
  if (route === "detail-outlet") return detailOutlet();
  if (route === "order") return orderScreen(route);
  if (route === "order-cart") return cartPage();
  if (route === "search-product") return searchProduct(false);
  if (route === "search-empty") return searchProduct(true);
  if (route === "product-detail") return productDetail();
  if (route.startsWith("checkout")) return checkout(route);
  if (route.startsWith("pickup-") || route.startsWith("delivery-")) {
    if (route.endsWith("detail")) return orderDetail(route);
    return orderStatus(route);
  }
  if (route === "voucher") return voucher();
  if (route === "payment") return payment();
  if (route === "app-invite") return appInvite();
  if (route === "success") return result(true);
  if (route === "unsuccess") return result(false);
  if (route === "scan" || route === "scan-flash") return scanScreen(route);
  if (route === "my-qr") return myQr();
  if (route === "history") return history();
  if (route === "favorite") return favorite();
  if (route === "profile") return profile();
  if (["edit-profile", "address", "card", "language", "privacy", "terms", "help"].includes(route)) return profileForm(route);
  return guestHome();
}

function addToCart(id) {
  const existing = state.cart.find((line) => line.id === id);
  if (existing) existing.qty += 1;
  else state.cart.push({ id, qty: 1 });
}

function bind() {
  const app = document.querySelector("#app");
  app.addEventListener("click", (event) => {
    const languageButton = event.target.closest("[data-language]");
    if (languageButton) {
      state.language = languageButton.dataset.language;
      localStorage.setItem("hopresso-language", state.language);
      go("home");
      return;
    }

    const languageToggle = event.target.closest("[data-language-toggle]");
    if (languageToggle) {
      state.language = isArabic() ? "English" : "Arabic";
      localStorage.setItem("hopresso-language", state.language);
      render();
      return;
    }

    const categoryButton = event.target.closest("[data-category]");
    if (categoryButton) {
      state.selectedCategory = categoryButton.dataset.category;
      render();
      return;
    }

    const sizeButton = event.target.closest("[data-size]");
    if (sizeButton) {
      state.selectedSize = sizeButton.dataset.size;
      render();
      return;
    }

    const milkButton = event.target.closest("[data-milk]");
    if (milkButton) {
      state.selectedMilk = milkButton.dataset.milk;
      render();
      return;
    }

    const cartInc = event.target.closest("[data-cart-inc]");
    if (cartInc) {
      addToCart(cartInc.dataset.cartInc);
      render();
      return;
    }

    const cartDec = event.target.closest("[data-cart-dec]");
    if (cartDec) {
      const existing = state.cart.find((line) => line.id === cartDec.dataset.cartDec);
      if (existing) existing.qty -= 1;
      state.cart = state.cart.filter((line) => line.qty > 0);
      render();
      return;
    }

    const productButton = event.target.closest("[data-product]");
    if (productButton) state.selectedProductId = productButton.dataset.product;

    const addButton = event.target.closest("[data-add]");
    if (addButton) {
      addToCart(addButton.dataset.add);
      const next = addButton.dataset.routeAfterAdd || addButton.dataset.route;
      if (next) go(next);
      else render();
      return;
    }

    const paymentButton = event.target.closest("[data-payment]");
    if (paymentButton) {
      state.paymentMethod = paymentButton.dataset.payment;
      state.paymentOpen = false;
      render();
      return;
    }

    const paymentToggle = event.target.closest("[data-payment-toggle]");
    if (paymentToggle) {
      state.paymentOpen = !state.paymentOpen;
      render();
      return;
    }

    const paymentOption = event.target.closest("[data-payment-option]");
    if (paymentOption) {
      state.paymentMethod = paymentOption.dataset.paymentOption;
      state.paymentOpen = false;
      render();
      return;
    }

    const voucherApply = event.target.closest("[data-voucher-apply]");
    if (voucherApply) {
      state.voucherApplied = Boolean(state.voucherCode.trim());
      render();
      return;
    }

    const routeButton = event.target.closest("[data-route]");
    if (routeButton) {
      state.paymentOpen = false;
      go(routeButton.dataset.route);
    }
  });

  app.addEventListener("input", (event) => {
    if (event.target.matches("[data-search]")) {
      state.search = event.target.value;
      render();
    }
    if (event.target.matches("[data-voucher-input]")) {
      state.voucherCode = event.target.value.toUpperCase();
      state.voucherApplied = false;
    }
  });

}

function render() {
  ensureDemoState(state.route);
  document.querySelector("#app").innerHTML = renderRoute(state.route);
  applyEnglishNumerals();
}

window.addEventListener("hashchange", () => {
  setHashRoute();
  render();
  window.scrollTo({ top: 0, left: 0 });
});

setHashRoute();
bind();
render();
