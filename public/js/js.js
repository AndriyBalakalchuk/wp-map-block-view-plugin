console.log(arrManufactMapData);
document.addEventListener('DOMContentLoaded', () => {
	const swiper = new Swiper('.swiper', {
		// ориентация
		direction: 'horizontal',
		// номер первого слайда
		initialSlide: 4,
		// количество слайдов за раз
		slidesPerView: 2,
		// в зависимости от ширины экрана 375, 565, 767, 1024
		breakpoints: {
			// 375.8: {
			// 	slidesPerView: 2,
			// },
			767.8: {
				slidesPerView: 3,
			},
			1024.8: {
				slidesPerView: 5,
			},
		},
		// точки нумерации
		pagination: {
			el: '.swiper-pagination',
		},
		// стрелки навигации
		navigation: {
			nextEl: '.swiper-btn-next',
			prevEl: '.swiper-btn-prev',
		},
		on: {
			// перед инициализацией слайдера
			beforeInit: function () {
				console.log('beforeInit');
				// получаем родительский элемент со слайдами
				const nodeParenElement = document.querySelector('.swiper-wrapper');
				// создаем массив с нужными элементами, для примера 10 штук
				const arrHtml = createElements(arrManufactMapData, 0, true, 20);
				// если все ок, перебираем массив и выводим перед инициализацией карусели
				if (nodeParenElement && Array.isArray(arrHtml)) {
					arrHtml.forEach(function (item) {
						nodeParenElement.insertAdjacentHTML('beforeend', item);
					});
				} else {
					console.log("Div блок '.swiper-wrapper' каруселі не знайдено, або елементи слайдерів не змогли бути сгенеровані.");
				}
			},
		},
	});

	// смена активного слайда
	swiper.on('slideChange', () => {
		// если слайд первый
		if (swiper.isBeginning) {
			console.log(`Первый`);
			setTimeout(() => {
				// получаю элемент первого слайда
				const objLastElement = swiper.slides[0];
				// получаю его номер в массиве из data атребута
				const numInArray = objLastElement.dataset.array;
				// вызываю функцию по созданию массива с новыми слайдами в нужном диапазоне
				const arrNewSliders = createElements(arrManufactMapData, +numInArray - 1, false, swiper.slidesPerViewDynamic());
				// добавляю новые слайды в начало
				swiper.prependSlide(arrNewSliders);
				// удаляю слайды из конца карусели
				swiper.removeSlide(getLastIndexes(swiper.slides.length, swiper.slidesPerViewDynamic()));
			}, swiper.params.speed);
		}
		// если слайд последний
		if (swiper.isEnd) {
			console.log(`Последний`);
			setTimeout(() => {
				// получаю элемент последнего слайда
				const objLastElement = swiper.slides[swiper.slides.length - 1];
				// получаю его номер в массиве из data атребута
				const numInArray = objLastElement.dataset.array;
				// вызываю функцию по созданию массива с новыми слайдами в нужном диапазоне
				const arrNewSliders = createElements(arrManufactMapData, +numInArray + 1, true, swiper.slidesPerViewDynamic());
				// добавляю новые слайды в конец
				swiper.appendSlide(arrNewSliders);
				// удаляю слайды из начала карусели
				swiper.removeSlide([...Array(swiper.slidesPerViewDynamic()).keys()]);
			}, swiper.params.speed);
		}
	});

	// функция создания html строки по шаблону
	// data - массив с данными
	// startIndex - индекс начального элемента
	// ascending - направление сортировки true - по возрастанию, false - по убыванию
	// count - количество элементов
	function createElements(data, startIndex, ascending, count) {
		let result = [];
		let currentIndex = startIndex;

		for (let i = 0; i < count; i++) {
			if (currentIndex < 0) {
				currentIndex = data.length - 1;
			} else if (currentIndex >= data.length) {
				currentIndex = 0;
			}

			const item = data[currentIndex];
			let strLogo = `<div class="slide__logo_image"><img src="${item.cover_link}" alt=""/></div>`;
			if (!item.cover_link) strLogo = `<div class="slide__logo_watermark"><div>${item.name}</div></div>`;
			const elementHtml = `
				<div class="swiper-slide" data-array='${currentIndex}'>
					<div class="slide">
						<div class="slide__wrapper">
							<div class="slide__body">
								<div class="slide__logo">
									${strLogo}
								</div>
								<div class="slide__text">
								<div class="slide__title">${item.name}</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				`;
			result.push(elementHtml);

			if (ascending) {
				currentIndex++;
			} else {
				currentIndex--;
			}
		}

		return result;
	}

	// функция создает массив для удаления с конце слайдов
	// totalElements - общее количество слайдов
	// n - количество слайдов для удаления
	function getLastIndexes(totalElements, n) {
		// Создаем массив с числами от 0 до totalElements - 1
		var arr = Array.from({ length: totalElements }, (_, i) => i);

		// Извлекаем последние n элементов
		return arr.slice(-n);
	}
});
