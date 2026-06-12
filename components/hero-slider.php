<?php
$slides = [];

// Settings වලින් Banners 3 සහ අකුරු ලබා ගැනීම
for ($i = 1; $i <= 3; $i++) {
    $slides[] = [
        'media' => $site_settings["hero_banner_$i"] ?? '',
        'title' => $site_settings["hero_{$i}_title"] ?? '',
        'subtitle' => $site_settings["hero_{$i}_subtitle"] ?? '',
        'desc' => $site_settings["hero_{$i}_desc"] ?? '',
        'btn_text' => $site_settings["hero_{$i}_btn_text"] ?? '',
        'btn_link' => $site_settings["hero_{$i}_btn_link"] ?? '',
        // Media නැත්නම් Default දාන්න පරණ පාට ටික
        'bg' => $i === 1 ? 'linear-gradient(135deg, #00a046 0%, #008038 50%, #006628 100%)' : ($i === 2 ? 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)' : 'linear-gradient(135deg, #00a046 0%, #008038 100%)')
    ];
}
?>

<section class="hero-slider-ugreen position-relative overflow-hidden">
    <div class="hero-slides-wrapper">
        <?php foreach ($slides as $i => $slide): ?>
            <div class="hero-slide<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>" style="background: <?php echo $slide['bg']; ?>">
                
                <?php if (!empty($slide['media'])): 
                    $ext = strtolower(pathinfo($slide['media'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['mp4', 'webm'])): ?>
                        <video src="uploads/settings/<?php echo $slide['media']; ?>" autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover; object-position: center; position: absolute; top: 0; left: 0; z-index: 0;"></video>
                    <?php else: ?>
                        <img src="uploads/settings/<?php echo $slide['media']; ?>" alt="Hero Banner" style="width: 100%; height: 100%; object-fit: cover; object-position: top; position: absolute; top: 0; left: 0; z-index: 0;">
                    <?php endif; ?>
                    
                    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0); z-index: 1;"></div>
                <?php endif; ?>

                <div class="container position-relative" style="z-index: 2;">
                    <div class="row min-vh-80 align-items-center py-5">
                        <div class="col-lg-7 slide-content-ugreen">
                            <?php if (!empty($slide['subtitle'])): ?>
                                <span class="slide-subtitle d-inline-block mb-2"><?php echo htmlspecialchars($slide['subtitle']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($slide['title'])): ?>
                                <h1 class="display-4 fw-bold text-white mb-3 slide-title-ugreen"><?php echo htmlspecialchars($slide['title']); ?></h1>
                            <?php endif; ?>
                            
                            <?php if (!empty($slide['desc'])): ?>
                                <p class="lead text-white opacity-90 mb-4"><?php echo htmlspecialchars($slide['desc']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($slide['btn_text'])): ?>
                                <a href="<?php echo htmlspecialchars($slide['btn_link'] ?: 'shop.php'); ?>" class="btn btn-light btn-lg rounded-pill px-4 fw-bold btn-slide-cta">
                                    <?php echo htmlspecialchars($slide['btn_text']); ?> <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="hero-dots" style="z-index: 10;">
        <?php foreach ($slides as $i => $slide): ?>
            <button type="button" class="hero-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>"></button>
        <?php endforeach; ?>
    </div>
</section>

<style>
.hero-slider-ugreen { min-height: 85vh; position: relative; }
.min-vh-80 { min-height: 85vh; }
.hero-slides-wrapper { position: relative; width: 100%; height: 100%; }
.hero-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transform: translateX(10px);
    transition: opacity 0.8s ease, transform 0.8s ease;
    display: flex;
    align-items: center;
}
.hero-slide.active {
    opacity: 1;
    transform: translateX(0);
    position: relative;
}
.hero-slide-inner, .hero-slide { min-height: 85vh; }
.slide-subtitle { color: rgba(255,255,255,0.9); font-size: 1rem; letter-spacing: 2px; text-transform: uppercase; text-shadow: 0 1px 4px rgba(0,0,0,0.5); }
.slide-title-ugreen { text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
.slide-content-ugreen .lead { opacity: 0.9; text-shadow: 0 1px 5px rgba(0,0,0,0.5); }
.btn-slide-cta { transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
.btn-slide-cta:hover { transform: translateX(5px); color: var(--primary); }
.hero-dots {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
}
.hero-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    border: none;
    background: rgba(255,255,255,0.4);
    padding: 0;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.hero-dot.active {
    background: #fff;
    width: 26px;
}
@media (max-width: 768px) {
    .hero-slider-ugreen { min-height: 70vh; }
    .slide-title-ugreen { font-size: 1.75rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    if (!slides.length) return;

    let current = 0;
    const total = slides.length;
    const delay = 4500;
    let slideInterval;

    function goTo(index) {
        slides[current].classList.remove('active');
        if(dots[current]) dots[current].classList.remove('active');
        current = (index + total) % total;
        slides[current].classList.add('active');
        if(dots[current]) dots[current].classList.add('active');
    }

    dots.forEach(dot => {
        dot.addEventListener('click', function() {
            const idx = parseInt(this.getAttribute('data-index')) || 0;
            goTo(idx);
            resetInterval(); 
        });
    });

    function resetInterval() {
        clearInterval(slideInterval);
        slideInterval = setInterval(function() {
            goTo(current + 1);
        }, delay);
    }

    resetInterval();
});
</script>
