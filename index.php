<?php
require_once 'config.php';
require_once 'functions.php';

$current_category = $_GET['cat'] ?? 'all';

$shippingRates = [];
try {
    $stmt = $pdo->query("SELECT * FROM shipping_rates");
    while ($row = $stmt->fetch()) { $shippingRates[$row['wilaya_id']] = $row; }
} catch (Exception $e) { $shippingRates = []; }

try {
    $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    $wilayas = $pdo->query("SELECT * FROM wilayas ORDER BY code ASC")->fetchAll();
    $sql = "SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'published'";
    $params = [];
    if ($current_category !== 'all') { $sql .= " AND c.slug = ?"; $params[] = $current_category; }
    $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $products = $stmt->fetchAll();
    foreach ($products as &$p) {
        $stmt = $pdo->prepare("SELECT size FROM product_sizes WHERE product_id = ?"); $stmt->execute([$p['id']]); $p['sizes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("SELECT color_name FROM product_colors WHERE product_id = ?"); $stmt->execute([$p['id']]); $p['colors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_default DESC"); $stmt->execute([$p['id']]); $p['images'] = $stmt->fetchAll();
        $p['default_image'] = $p['images'][0]['image_path'] ?? $p['image'];
    }
    unset($p);
} catch (Exception $e) { $cats = []; $wilayas = []; $products = []; }

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>For you by mb — Boutique Officielle</title>
    <link rel="icon" href="1.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..800;1,400..800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{burgundy:'#5A1930',brandPink:'#D394B6',bgSoft:'#E5C8DD',lightPink:'#E7B8CF'},fontFamily:{serif:['"Playfair Display"','serif'],sans:['"Plus Jakarta Sans"','sans-serif']}}}}</script>
    <style>html{scroll-behavior:smooth}body{overflow-x:hidden;max-width:100vw}img{max-width:100%;height:auto}@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}.animate-float{animation:float 4s ease-in-out infinite}.no-scrollbar::-webkit-scrollbar{display:none}.no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}.product-img{transition:opacity .3s ease}</style>
</head>
<body class="bg-[#FDF8FA] text-slate-800 font-sans antialiased min-h-screen overflow-x-hidden w-full max-w-[100vw]">

<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-sm border-b border-lightPink/20 w-full">
    <div class="w-full px-3 md:px-4 py-2 md:py-3 flex items-center justify-between max-w-7xl mx-auto">
        <a href="index.php" class="flex items-center gap-2 md:gap-3"><img src="1.png" alt="For you by mb" class="h-12 md:h-20 w-auto object-contain"><span class="font-serif italic font-bold text-lg md:text-2xl text-burgundy">For you by mb</span></a>
        <button onclick="openCart()" class="relative p-2 md:p-2.5 text-burgundy hover:bg-lightPink/10 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg><span id="cart-badge" class="absolute -top-1 -right-1 px-1.5 py-0.5 text-[10px] font-extrabold text-white bg-burgundy rounded-full hidden">0</span></button>
    </div>
    <nav class="bg-white border-t border-lightPink/10 w-full"><div class="w-full px-2 md:px-4 flex overflow-x-auto no-scrollbar gap-1 py-2 md:py-3 md:justify-center max-w-7xl mx-auto"><a href="index.php" class="px-3 md:px-5 py-2 md:py-2.5 rounded-full text-[11px] md:text-xs font-semibold whitespace-nowrap <?= $current_category==='all'?'bg-burgundy text-white':'text-burgundy hover:bg-lightPink/20' ?>">Tous</a><?php foreach($cats as $cat): ?><a href="?cat=<?= $cat['slug'] ?>" class="px-3 md:px-5 py-2 md:py-2.5 rounded-full text-[11px] md:text-xs font-semibold whitespace-nowrap <?= $current_category===$cat['slug']?'bg-burgundy text-white':'text-burgundy hover:bg-lightPink/20' ?>"><?= htmlspecialchars($cat['name']) ?></a><?php endforeach; ?></div></nav>
</header>

<section class="relative bg-gradient-to-br from-[#E5C8DD]/50 via-[#E7B8CF]/20 to-[#FDF8FA] pt-6 md:pt-8 pb-8 md:pb-12 px-4 border-b border-lightPink/10 w-full overflow-hidden"><div class="max-w-3xl mx-auto text-center relative z-10 flex flex-col items-center"><img src="1.png" alt="For you by mb" class="w-20 h-20 md:w-32 md:h-32 rounded-2xl object-contain border-2 border-burgundy/30 shadow-xl mb-4 md:mb-5 animate-float"><h1 class="font-serif italic font-extrabold text-3xl sm:text-4xl md:text-6xl text-burgundy mb-2 md:mb-3 px-2">For you by mb</h1><p class="text-sm sm:text-base md:text-lg text-burgundy/80 font-medium mb-5 md:mb-6 px-4">Votre boutique beauté, bien-être et mode féminine.</p><button onclick="document.getElementById('products-section').scrollIntoView({behavior:'smooth'})" class="bg-burgundy text-white px-6 md:px-8 py-3 md:py-3.5 rounded-full text-sm md:text-base font-bold shadow-md hover:bg-[#431223] active:scale-95 transition">Découvrir les produits</button></div></section>

<main id="products-section" class="w-full px-3 md:px-4 py-6 md:py-10 max-w-7xl mx-auto"><h2 class="font-serif italic font-bold text-xl md:text-3xl text-burgundy text-center mb-6 md:mb-8">Nos Produits</h2><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
    <?php foreach($products as $p): ?>
    <div class="bg-white rounded-2xl border border-lightPink/20 overflow-hidden shadow-sm hover:shadow-lg transition flex flex-col group"><div class="relative aspect-square overflow-hidden bg-slate-50"><img src="<?= htmlspecialchars($p['default_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-img w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" id="img-<?= $p['id'] ?>"><div class="absolute top-2 md:top-3 right-2 md:right-3 bg-white/95 px-2.5 md:px-3.5 py-1 md:py-1.5 rounded-full shadow-sm"><span class="text-burgundy font-extrabold text-xs md:text-sm"><?= format_price($p['price']) ?></span></div></div><div class="p-3 md:p-5 flex-1 flex flex-col justify-between"><div><span class="text-[9px] md:text-[10px] font-bold text-brandPink uppercase tracking-widest"><?= htmlspecialchars($p['cat_name']) ?></span><h3 class="font-serif font-bold text-slate-900 text-sm md:text-base mb-1"><?= htmlspecialchars($p['name']) ?></h3><p class="text-[11px] md:text-xs text-slate-500"><?= htmlspecialchars($p['description']) ?></p>
    <?php if(!empty($p['sizes'])): ?><div class="mt-2 md:mt-3"><span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase">Tailles</span><div class="flex flex-wrap gap-1 md:gap-1.5 mt-1"><?php foreach($p['sizes'] as $sz): ?><button onclick="selectSize('<?= $p['id'] ?>','<?= $sz ?>')" class="sz-<?= $p['id'] ?> px-2 md:px-3 py-1 md:py-1.5 text-[10px] md:text-xs font-bold rounded-lg border border-slate-200 text-slate-600 hover:border-burgundy transition" data-size="<?= $sz ?>"><?= $sz ?></button><?php endforeach; ?></div></div><?php endif; ?>
    <?php if(!empty($p['colors'])): ?><div class="mt-2 md:mt-3"><span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase">Couleurs</span><div class="flex flex-wrap gap-1 md:gap-1.5 mt-1"><?php foreach($p['colors'] as $col): ?><button onclick="selectColor('<?= $p['id'] ?>','<?= $col ?>')" class="cl-<?= $p['id'] ?> px-2 md:px-3 py-1 md:py-1.5 text-[10px] md:text-xs font-bold rounded-lg border border-slate-200 text-slate-600 hover:border-burgundy hover:text-burgundy transition" data-color="<?= $col ?>"><?= $col ?></button><?php endforeach; ?></div></div><?php endif; ?>
    </div><button onclick="addToCart(<?= $p['id'] ?>)" class="mt-3 md:mt-4 w-full bg-burgundy text-white py-2.5 md:py-3 rounded-xl text-xs md:text-sm font-bold hover:bg-[#431223] active:scale-95 transition">Ajouter au panier</button></div></div>
    <?php endforeach; ?>
</div></main>

<div id="cart-overlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="closeCart()"></div>
<div id="cart-drawer" class="fixed top-0 right-0 w-full max-w-[95vw] md:max-w-md h-full bg-white shadow-2xl z-50 transform translate-x-full transition duration-300 flex flex-col"><div class="px-4 md:px-5 py-3 md:py-4 border-b flex items-center justify-between bg-burgundy/5"><h3 class="font-serif font-bold text-base md:text-lg text-burgundy">Mon Panier</h3><button onclick="closeCart()" class="p-1.5 hover:bg-slate-100 rounded-full text-slate-400"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div><div id="cart-body" class="flex-1 overflow-y-auto px-3 md:px-5 py-3 md:py-4"></div><div id="cart-footer" class="p-3 md:p-5 border-t bg-slate-50 hidden"></div></div>

<div id="checkout-overlay" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-2 md:p-4"><div class="bg-white rounded-2xl shadow-2xl w-full max-w-[98vw] md:max-w-lg max-h-[90vh] overflow-y-auto p-4 md:p-6"><div class="flex items-center gap-2 md:gap-3 mb-3 md:mb-4"><img src="1.png" alt="Logo" class="w-8 h-8 md:w-10 md:h-10 rounded-lg object-contain"><h3 class="font-serif font-bold text-lg md:text-xl text-burgundy">Finaliser la commande</h3></div>
<form id="checkout-form" class="space-y-2 md:space-y-3"><input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><div class="grid grid-cols-2 gap-2 md:gap-3"><div><label class="text-[10px] md:text-xs font-bold text-slate-500">Nom *</label><input type="text" name="lastname" id="c-lastname" required class="w-full px-2 md:px-3 py-2 md:py-2.5 border rounded-xl text-xs md:text-sm"></div><div><label class="text-[10px] md:text-xs font-bold text-slate-500">Prénom *</label><input type="text" name="firstname" id="c-firstname" required class="w-full px-2 md:px-3 py-2 md:py-2.5 border rounded-xl text-xs md:text-sm"></div></div><div><label class="text-[10px] md:text-xs font-bold text-slate-500">Téléphone *</label><input type="tel" name="phone" id="c-phone" required class="w-full px-2 md:px-3 py-2 md:py-2.5 border rounded-xl text-xs md:text-sm" placeholder="0550123456"></div><div class="grid grid-cols-2 gap-2 md:gap-3"><div><label class="text-[10px] md:text-xs font-bold text-slate-500">Wilaya *</label><select name="wilaya" id="c-wilaya" required onchange="loadCommunes(this.value)" class="w-full px-2 md:px-3 py-2 md:py-2.5 border rounded-xl text-xs md:text-sm"><option value="">Choisir...</option><?php foreach($wilayas as $w): ?><option value="<?= $w['id'] ?>"><?= $w['code'] ?> - <?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?></select></div><div><label class="text-[10px] md:text-xs font-bold text-slate-500">Commune *</label><select name="commune" id="c-commune" required class="w-full px-2 md:px-3 py-2 md:py-2.5 border rounded-xl text-xs md:text-sm"><option value="">Wilaya d'abord</option></select></div></div><div><label class="text-[10px] md:text-xs font-bold text-slate-500">Adresse complète *</label><textarea name="address" id="c-address" required rows="3" class="w-full px-2 md:px-3 py-2 md:py-2.5 border rounded-xl text-xs md:text-sm" placeholder="N° de rue, Quartier, Ville, Étage, Code postal..."></textarea></div>
<div><label class="text-[10px] md:text-xs font-bold text-slate-500">Livraison</label><div class="grid grid-cols-2 gap-2" id="shipping-options"><label class="flex items-center gap-1 md:gap-2 p-2 md:p-3 border rounded-xl cursor-pointer text-[10px] md:text-sm"><input type="radio" name="delivery" value="domicile" checked onchange="updateShippingFromWilaya()"> <span>Domicile (<span id="domicile-price">--</span> DA)</span></label><label class="flex items-center gap-1 md:gap-2 p-2 md:p-3 border rounded-xl cursor-pointer text-[10px] md:text-sm" id="bureau-option"><input type="radio" name="delivery" value="bureau" onchange="updateShippingFromWilaya()"> <span>Bureau (<span id="bureau-price">--</span> DA)</span></label></div><p id="shipping-unavailable" class="text-[10px] text-red-500 hidden mt-1">⚠️ Livraison non disponible pour cette wilaya</p></div>
<div class="bg-green-50 p-2 md:p-3 rounded-xl text-[11px] md:text-sm text-green-800">💰 Paiement à la livraison</div><div class="flex justify-between font-bold pt-2 border-t text-sm md:text-base"><span>Total</span><span id="checkout-total" class="text-lg md:text-xl text-burgundy">0 DA</span></div><button type="submit" class="w-full bg-burgundy text-white py-3 md:py-3.5 rounded-xl text-sm md:text-base font-bold hover:bg-[#431223] active:scale-95 transition">Confirmer la commande</button><button type="button" onclick="closeCheckout()" class="w-full bg-slate-200 text-slate-700 py-2 md:py-2.5 rounded-xl text-sm font-semibold">Annuler</button></form></div></div>

<div id="toast" class="fixed bottom-4 md:bottom-6 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-4 md:px-5 py-2 md:py-3 rounded-xl shadow-lg text-xs md:text-sm z-50 translate-y-24 opacity-0 transition duration-300 pointer-events-none max-w-[90vw] text-center"><span id="toast-msg"></span></div>

<footer class="bg-burgundy text-white/70 py-6 md:py-8 mt-8 md:mt-12 text-center text-xs md:text-sm w-full"><img src="1.png" alt="For you by mb" class="w-10 h-10 md:w-12 md:h-12 mx-auto rounded-lg object-contain mb-2 opacity-80"><p class="font-serif italic text-base md:text-lg text-white mb-1">For you by mb</p><p>© <?= date('Y') ?> Tous droits réservés.</p><a href="login.php" class="text-white/50 hover:text-white text-[10px] md:text-xs mt-2 inline-block">Espace Administration</a></footer>

<script>
const productsData = <?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let cart = [], selectedSizes = {}, selectedColors = {};
const shippingRates = <?php echo json_encode($shippingRates); ?>;
let shippingFee = 0;

const productImages = {};
productsData.forEach(p => { productImages[p.id] = {}; if(p.images){ p.images.forEach(img => { productImages[p.id][img.color_name] = img.image_path; }); } });

function selectSize(pid, size) {selectedSizes[pid]=size;document.querySelectorAll('.sz-'+pid).forEach(b=>{b.classList.remove('bg-burgundy','text-white','border-burgundy');b.classList.add('border-slate-200','text-slate-600')});const a=document.querySelector('.sz-'+pid+'[data-size="'+size+'"]');if(a){a.classList.add('bg-burgundy','text-white','border-burgundy');a.classList.remove('border-slate-200','text-slate-600')}}
function selectColor(pid, color) {selectedColors[pid]=color;document.querySelectorAll('.cl-'+pid).forEach(b=>{b.classList.remove('bg-burgundy','text-white','border-burgundy');b.classList.add('border-slate-200','text-slate-600')});const a=document.querySelector('.cl-'+pid+'[data-color="'+color+'"]');if(a){a.classList.add('bg-burgundy','text-white','border-burgundy');a.classList.remove('border-slate-200','text-slate-600')};if(productImages[pid]&&productImages[pid][color]){const imgEl=document.getElementById('img-'+pid);if(imgEl){imgEl.style.opacity='0';setTimeout(function(){imgEl.src=productImages[pid][color];imgEl.style.opacity='1'},150)}}}

function addToCart(pid){
    const p=productsData.find(x=>x.id==pid);
    const size=selectedSizes[pid]||(p.sizes&&p.sizes.length>0?p.sizes[0]:null);
    const color=selectedColors[pid]||(p.colors&&p.colors.length>0?p.colors[0]:null);
    if(p.sizes&&p.sizes.length>0&&!selectedSizes[pid]){showToast('⚠️ Veuillez choisir une taille');return;}
    if(p.colors&&p.colors.length>0&&!selectedColors[pid]){showToast('⚠️ Veuillez choisir une couleur');return;}
    let cartImage=p.default_image;
    if(color&&productImages[pid]&&productImages[pid][color]){cartImage=productImages[pid][color]}
    const cartId=pid+'-'+(size||'')+'-'+(color||'');
    const ex=cart.find(x=>x.cartId===cartId);
    if(ex)ex.qty++;else cart.push({cartId,product_id:p.id,name:p.name,price:p.price,image:cartImage,size,color,qty:1});
    updateBadge();showToast(p.name+' ajouté !');
}
function updateBadge(){const t=cart.reduce((s,i)=>s+i.qty,0);const b=document.getElementById('cart-badge');t>0?(b.innerText=t,b.classList.remove('hidden')):b.classList.add('hidden')}
function openCart(){document.getElementById('cart-drawer').classList.remove('translate-x-full');document.getElementById('cart-overlay').classList.remove('hidden');renderCart();document.body.style.overflow='hidden'}
function closeCart(){document.getElementById('cart-drawer').classList.add('translate-x-full');document.getElementById('cart-overlay').classList.add('hidden');document.body.style.overflow=''}
function renderCart(){const body=document.getElementById('cart-body'),footer=document.getElementById('cart-footer');if(cart.length===0){body.innerHTML='<div class="text-center py-8 md:py-12"><p class="font-bold text-slate-800 text-sm md:text-base mb-1">Panier vide</p></div>';footer.classList.add('hidden')}else{const sub=cart.reduce((s,i)=>s+(i.price*i.qty),0);body.innerHTML=cart.map(i=>`<div class="flex gap-2 md:gap-3 bg-white p-2 md:p-3 rounded-xl border mb-2 md:mb-3"><img src="${i.image}" class="w-12 h-12 md:w-16 md:h-16 rounded-lg object-cover"><div class="flex-1"><h4 class="font-bold text-[10px] md:text-xs">${i.name}</h4>${i.size?`<span class="text-[9px] md:text-[10px] text-slate-400">Taille: ${i.size}</span> `:''}${i.color?`<span class="text-[9px] md:text-[10px] text-slate-400">Couleur: ${i.color}</span>`:''}<div class="flex justify-between mt-1 md:mt-2"><span class="text-[10px] md:text-xs font-bold text-burgundy">${(i.price*i.qty).toLocaleString('fr-FR')} DA</span><span class="text-[9px] md:text-xs">Qté: ${i.qty}</span></div></div><button onclick="removeFromCart('${i.cartId}')" class="text-slate-400 hover:text-red-500 text-sm md:text-base">🗑</button></div>`).join('');footer.classList.remove('hidden');footer.innerHTML=`<div class="flex justify-between font-bold text-sm md:text-base mb-2 md:mb-3"><span>Sous-total</span><span class="text-burgundy">${sub.toLocaleString('fr-FR')} DA</span></div><button onclick="openCheckout()" class="w-full bg-burgundy text-white py-3 md:py-3.5 rounded-xl text-sm md:text-base font-bold hover:bg-[#431223]">Commander</button>`}}
function removeFromCart(cartId){cart=cart.filter(i=>i.cartId!==cartId);updateBadge();renderCart()}
function openCheckout(){if(cart.length===0)return;closeCart();shippingFee=0;const t=cart.reduce((s,i)=>s+(i.price*i.qty),0);document.getElementById('checkout-total').innerText=t.toLocaleString('fr-FR')+' DA';document.getElementById('domicile-price').innerText='--';document.getElementById('bureau-price').innerText='--';document.getElementById('shipping-unavailable').classList.add('hidden');document.getElementById('bureau-option').classList.remove('opacity-50','pointer-events-none');document.querySelector('input[value="domicile"]').checked=true;document.getElementById('c-wilaya').value='';document.getElementById('c-commune').innerHTML='<option value="">Wilaya d\'abord</option>';document.getElementById('checkout-overlay').classList.remove('hidden');document.getElementById('checkout-overlay').classList.add('flex')}
function closeCheckout(){document.getElementById('checkout-overlay').classList.add('hidden');document.getElementById('checkout-overlay').classList.remove('flex')}
function updateShippingFromWilaya(){const wilayaId=parseInt(document.getElementById('c-wilaya').value);const deliveryMethod=document.querySelector('input[name="delivery"]:checked').value;const rates=shippingRates[wilayaId];const unavailable=document.getElementById('shipping-unavailable');const bureauOption=document.getElementById('bureau-option');if(rates){const bp=rates.bureau;const dp=rates.domicile;document.getElementById('domicile-price').innerText=dp?dp.toLocaleString('fr-FR'):'غير متوفر';document.getElementById('bureau-price').innerText=bp?bp.toLocaleString('fr-FR'):'غير متوفر';if(deliveryMethod==='domicile'&&dp){shippingFee=dp;unavailable.classList.add('hidden');bureauOption.classList.remove('opacity-50','pointer-events-none')}else if(deliveryMethod==='bureau'&&bp){shippingFee=bp;unavailable.classList.add('hidden');bureauOption.classList.remove('opacity-50','pointer-events-none')}else if(deliveryMethod==='domicile'&&!dp){unavailable.classList.remove('hidden');shippingFee=0}else if(deliveryMethod==='bureau'&&!bp){unavailable.classList.remove('hidden');shippingFee=0;document.querySelector('input[value="domicile"]').checked=true;updateShippingFromWilaya();return}if(!bp){bureauOption.classList.add('opacity-50','pointer-events-none')}else{bureauOption.classList.remove('opacity-50','pointer-events-none')}}else{shippingFee=0;document.getElementById('domicile-price').innerText='--';document.getElementById('bureau-price').innerText='--'}const t=cart.reduce((s,i)=>s+(i.price*i.qty),0);document.getElementById('checkout-total').innerText=(t+shippingFee).toLocaleString('fr-FR')+' DA'}
function loadCommunes(wid){fetch('api.php?action=get_communes&wilaya_id='+wid).then(r=>r.json()).then(d=>{const s=document.getElementById('c-commune');s.innerHTML='<option value="">Choisir...</option>';if(d.success)d.communes.forEach(x=>{s.innerHTML+='<option value="'+x.name+'">'+x.name+'</option>'})});updateShippingFromWilaya()}
document.getElementById('checkout-form').addEventListener('submit',function(e){e.preventDefault();const fd=new FormData(this);fd.append('action','submit_order');fd.append('cart',JSON.stringify(cart));fd.append('shipping_fee',shippingFee);if(document.getElementById('c-phone').value.trim().length<8){showToast('❌ Téléphone invalide');return}fetch('api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){cart=[];updateBadge();closeCheckout();renderCart();showToast('✅ Commande confirmée !')}else showToast('❌ '+(d.message||'Erreur'))}).catch(()=>showToast('❌ Erreur réseau'))})
function showToast(m){document.getElementById('toast-msg').innerText=m;const t=document.getElementById('toast');t.classList.remove('translate-y-24','opacity-0');t.classList.add('translate-y-0','opacity-100');setTimeout(()=>{t.classList.add('translate-y-24','opacity-0');t.classList.remove('translate-y-0','opacity-100')},3000)}
</script>
</body>
</html>