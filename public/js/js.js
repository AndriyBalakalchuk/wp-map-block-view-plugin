console.log(arrManufactMapData);
document.addEventListener('DOMContentLoaded', () => {
	const carousel = document.getElementById('carousel');
	// если такого элемента нет выходим
	if (!carousel) return;
	// количество элементов в карусели
	let numCaurosels = 5;
	// текущий индекс
	let currentIndex = 0;

	function createCarouselItems() {
		carousel.innerHTML = '';
		for (let i = 0; i < numCaurosels; i++) {
			const itemIndex = (currentIndex + i) % arrManufactMapData.length;
			const item = document.createElement('div');
			// добавляем имя класса
			item.className = 'carousel__item';
			// добавляем id элемента
			item.id = `item-${i}`;
			// добавляем ширину блока в style
			item.style.width = `${100 / numCaurosels}%`;
			// тег с изображение
			let strImage = `<img src="${arrManufactMapData[itemIndex].cover_link}">`;
			// если src на изображение отсутствует выводим блок с текстом
			if (!arrManufactMapData[itemIndex].cover_link) strImage = `<div class="carousel-watermark"><div class="carousel-watermark__text">${arrManufactMapData[itemIndex].name}</div></div>`;
			// добавляем содержимое
			item.innerHTML = `
		<div class="carousel__image">
			${strImage}
		</div>
		<div class="carousel__tex-box">
			<div class="carousel__title" title="${arrManufactMapData[itemIndex].name}">${arrManufactMapData[itemIndex].name}</div>
		</div>
		`;
			carousel.appendChild(item);
		}
	}

	function updateCarousel(direction) {
		currentIndex = (currentIndex + direction + arrManufactMapData.length) % arrManufactMapData.length;
		createCarouselItems();
	}

	document.getElementById('prev').addEventListener('click', () => {
		// carousel.classList.remove("slide-out-right");
		carousel.classList.add('slide-out-left');
		updateCarousel(-numCaurosels);
		setTimeout(() => {
			carousel.classList.remove('slide-out-left');
		}, 500);
	});

	document.getElementById('next').addEventListener('click', () => {
		carousel.classList.add('slide-out-right');
		updateCarousel(numCaurosels);
		setTimeout(() => {
			carousel.classList.remove('slide-out-right');
		}, 500);
	});

	createCarouselItems();
});
