<div id="map-block-view">
	<!-- Map -->
	<div class="global-map">
		<div id="map" class="global-map__body"></div>
	</div>
	<?php  if($boolShowLogosSlider){ ?>
	<!-- Головний контейнер зі слайдами -->
	<div class="container">
		<div class="global-map-swiper swiper" id="swiper-map">
			<!-- Обгортка -->
			<div class="swiper-wrapper">
				<!-- Слайди -->
			</div>
			<!-- If we need pagination -->
			<!-- <div class="swiper-pagination"></div> -->
			<!-- кнопки навігації -->
			<div class="swiper-btn-prev"></div>
			<div class="swiper-btn-next"></div>
		</div>
	</div>
	<?php  } ?>
</div>
