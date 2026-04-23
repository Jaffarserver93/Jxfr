<div id="anime-trending">
        <div class="container">
            <section class="block_area block_area_trending">
                <div class="block_area-header">
                    <div class="bah-heading">
                        <h2 class="cat-heading">Trending</h2>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="block_area-content">
                    <div class="trending-list" id="trending-home">
                        <div class="swiper-container swiper-container-initialized swiper-container-horizontal">
                            <div class="swiper-wrapper" style="transition-duration: 0ms; transform: translate3d(0px, 0px, 0px);">
                                <?php foreach ($data['trending'] as $item): ?>
                                    <div class="swiper-slide item-qtip" data-id="<?= htmlspecialchars($item['id']) ?>" style="width: 209px; margin-right: 15px;">
                                        <div class="item">
                                            <div class="number">
                                                <span><?= htmlspecialchars($item['number']) ?></span>
                                            <div class="film-title dynamic-name" data-title="<?= htmlspecialchars($item['title']) ?>" data-jname="<?= htmlspecialchars($item['jname']) ?>">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </div>
                                        </div>
                                        <a href="/details/<?= htmlspecialchars($item['id']) ?>" class="film-poster">
                                            <img data-src="<?= htmlspecialchars($item['poster']) ?>" class="film-poster-img lazyloaded" alt="<?= htmlspecialchars($item['title']) ?>" src="<?= htmlspecialchars($item['poster']) ?>">
                                        </a>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="clearfix"></div>
                        <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span></div>
                        <div class="trending-navi">
                            <div class="navi-next" tabindex="0" role="button" aria-label="Next slide" aria-disabled="false"><i class="fas fa-angle-right"></i></div>
                            <div class="navi-prev swiper-button-disabled" tabindex="-1" role="button" aria-label="Previous slide" aria-disabled="true"><i class="fas fa-angle-left"></i></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>