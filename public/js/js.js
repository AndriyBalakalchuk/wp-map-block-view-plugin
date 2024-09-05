console.log(arrManufactMapData);
document.addEventListener('DOMContentLoaded', () => {
	// if the variable with the array does not exist and the array is not empty, return
	if (!arrManufactMapData || arrManufactMapData.length === 0) {
		// hide the map
		document.getElementById('map-block-view').style.display = 'none';
		return;
	}

	// creating the slider
	const swiper = new Swiper('.swiper', {
		// orientation
		direction: 'horizontal',
		// number of the first slide
		initialSlide: 4,
		// number of slides at a time
		slidesPerView: 2,
		// space between slides
		spaceBetween: 10,
		// depending on screen width 375, 565, 767, 1024
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
		// pagination dots
		pagination: {
			el: '.swiper-pagination',
		},
		// navigation arrows
		navigation: {
			nextEl: '.swiper-btn-next',
			prevEl: '.swiper-btn-prev',
		},
		on: {
			// before initializing the slider
			beforeInit: function () {
				// get the parent element with slides
				const nodeParenElement = document.querySelector('.swiper-wrapper');
				// create an array with the necessary elements, for example, 10 pieces
				const arrHtml = createElements(arrManufactMapData, 0, true, 20);
				// if everything is ok, iterate over the array and output it before the carousel initialization
				if (nodeParenElement && Array.isArray(arrHtml)) {
					arrHtml.forEach(function (item) {
						nodeParenElement.insertAdjacentHTML('beforeend', item);
					});
				} else {
					console.log("Div block '.swiper-wrapper' carousel not found, or slide elements could not be generated.");
				}
			},
		},
	});

	// change the active slide
	swiper.on('slideChange', () => {
		// if the slide is the first
		if (swiper.isBeginning) {
			setTimeout(() => {
				// get the element of the first slide
				const objLastElement = swiper.slides[0];
				// get its number in the array from the data attribute
				const numInArray = objLastElement.dataset.array;
				// call the function to create an array with new slides in the desired range
				const arrNewSliders = createElements(arrManufactMapData, +numInArray - 1, false, swiper.slidesPerViewDynamic());
				// add new slides to the beginning
				swiper.prependSlide(arrNewSliders);
				// remove slides from the end of the carousel
				swiper.removeSlide(getLastIndexes(swiper.slides.length, swiper.slidesPerViewDynamic()));
			}, swiper.params.speed);
		}
		// if the slide is the last
		if (swiper.isEnd) {
			setTimeout(() => {
				// get the element of the last slide
				const objLastElement = swiper.slides[swiper.slides.length - 1];
				// get its number in the array from the data attribute
				const numInArray = objLastElement.dataset.array;
				// call the function to create an array with new slides in the desired range
				const arrNewSliders = createElements(arrManufactMapData, +numInArray + 1, true, swiper.slidesPerViewDynamic());
				// add new slides to the end
				swiper.appendSlide(arrNewSliders);
				// remove slides from the beginning of the carousel
				swiper.removeSlide([...Array(swiper.slidesPerViewDynamic()).keys()]);
			}, swiper.params.speed);
		}
	});

	// function to create an HTML string based on a template
	// data - array with data
	// startIndex - index of the initial element
	// ascending - sorting direction true - ascending, false - descending
	// count - number of elements
	function createElements(data, startIndex, ascending, count) {
		// delete last element in array
		let arrData = data.slice(0, data.length - 1);
		let result = [];
		let currentIndex = startIndex;

		for (let i = 0; i < count; i++) {
			if (currentIndex < 0) {
				currentIndex = arrData.length - 1;
			} else if (currentIndex >= arrData.length) {
				currentIndex = 0;
			}

			const item = arrData[currentIndex];
			let strLogo = `<div class="slide__logo_image"><img src="${item.cover_link}" alt=""/></div>`;
			if (!item.cover_link) strLogo = `<div class="slide__logo_watermark"><div>${item.name}</div></div>`;
			const elementHtml = `
				<div class="swiper-slide" data-array='${currentIndex}' onclick="openMarkerPopup('${item.id}')">
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

	// function creates an array to remove slides from the end
	// totalElements - total number of slides
	// n - number of slides to remove
	function getLastIndexes(totalElements, n) {
		// Create an array with numbers from 0 to totalElements - 1
		const arr = Array.from({ length: totalElements }, (_, i) => i);

		// extract the last n elements
		return arr.slice(-n);
	}

	// Функция для получения значения куки по имени
	function getCookie(name) {
		const value = '; ' + document.cookie;
		let parts = value.split('; ' + name + '=');
		if (parts.length === 2) return parts.pop().split(';').shift();
	}

	// Функция для определения языка из куки
	function getLanguageFromCookie() {
		let language = getCookie('pll_language');
		if (language === 'en') {
			return 'en';
		}
		return 'de'; // По умолчанию - немецкий
	}

	// Получение текущего языка
	const currentLanguage = getLanguageFromCookie();

	// Инициализация карты
	const map = L.map('map');

	// Добавление слоя CartoDB Positron
	L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
	}).addTo(map);

	// Функция для создания содержимого popup
	function createPopupContent(marker) {
		// определяем язык
		const strDescription = currentLanguage === 'en' ? marker.description_en : marker.description_de;
		// создаем логотип
		let strLogo = `<div class="popup__logo_image"><img src="${marker.cover_link}" alt="${marker.name} logo"></div>`;
		// если нет логотипа
		if (!marker.cover_link) strLogo = `<div class="popup__logo_watermark"><div>${marker.name}</div></div>`;
		// создаем контейнер с информации о размере предприятия m²
		const strArea = marker.area > 0 ? `<div class="popup__area">${marker.area} m²</div>` : '';
		return `
				<div class="popup">
					<div class="popup__body">
						<div class="popup__logo">
							${strLogo}
						</div>
						<div class="popup__text">
							<div class="popup__title">${marker.name}</div>
							<div class="popup__description">${strDescription}</div>
							${strArea}
						</div>
					</div>
				</div>
			`;
	}

	// Функция для выбора иконки в зависимости от status
	function getMarkerIcon(status, area = 0) {
		const strPath = currentLanguage === 'en' ? '../wp-content/plugins/wp-map-block-view-plugin/public/images/' : './wp-content/plugins/wp-map-block-view-plugin/public/images/';

		const objMarkerIcon = {
			iconSize: [30, 30],
			popupAnchor: [0, -10],
		};

		// задаем размер иконки в зависимости от значения area (площадь),
		//	если площадь от 0 до 500 - sm, если от 500 до 1000 - md, если больше 1000 - xl
		const strIconSize = area < 500 ? 'sm' : area < 1000 ? 'md' : 'xl';

		// выбираем иконку в зависимости от значения status
		switch (status) {
			case 'Active':
				objMarkerIcon.iconUrl = `${strPath}icon-02-${strIconSize}.png`;
				break;
			case 'Negotiations':
				objMarkerIcon.iconUrl = `${strPath}icon-03-${strIconSize}.png`;
				break;
			case 'HOME':
				objMarkerIcon.iconUrl = `${strPath}icon-05-xl.png`;
				objMarkerIcon.className = `home-marker`;
				break;
			default:
				objMarkerIcon.iconUrl = `${strPath}icon-01-${strIconSize}.png`;
		}
		// возвращаем объект с иконкой
		return L.icon(objMarkerIcon);
	}

	// Добавление дуги на карту между точками
	arrManufactMapData.forEach(function (marker) {
		// Получить массив из Json объекта
		const arrLinkTo = JSON.parse(marker.link_to);
		// Если массив не пустой
		if (arrLinkTo.length > 0) {
			// Перебираем массив
			arrLinkTo.forEach(function (objLink) {
				const arrStart = objLink.start.split(',').map(Number);
				const arrEnd = objLink.end.split(',').map(Number);
				// Координаты двух точек
				const latlngs = [
					[arrStart[1], arrStart[0]],
					[arrEnd[1], arrEnd[0]],
				];
				// Создаем арочную полилинию с настройками
				const arcedPolyline = new L.ArcedPolyline(latlngs, {
					distanceToHeight: new L.LinearFunction([0, 0], [1000, 500]), // Настраиваем высоту арки
					color: '#FF0000', // Цвет линии
					weight: 2, // Толщина линии
					opacity: 0.4, // Прозрачность
				});
				// Добавляем линию на карту
				map.addLayer(arcedPolyline);
			});
		}
	});

	// Объект для хранения маркеров Leaflet
	const leafletMarkers = {};

	// Добавление меток на карту с выбором кастомной иконки
	arrManufactMapData.forEach(function (marker) {
		const coords = marker.lat_and_long.split(',').map(Number);
		const popupContent = createPopupContent(marker);
		const markerIcon = getMarkerIcon(marker.status, marker.area);
		const leafletMarker = L.marker([coords[0], coords[1]], { icon: markerIcon }).bindPopup(popupContent, { minWidth: 300 }).addTo(map);
		leafletMarkers[marker.id] = leafletMarker; // Сохранение маркера в объект
	});

	// Автоматический захват всех маркеров на карте
	const bounds = new L.LatLngBounds();
	arrManufactMapData.forEach(function (marker) {
		const coords = marker.lat_and_long.split(',').map(Number);
		bounds.extend([coords[0], coords[1]]);
	});
	map.fitBounds(bounds);

	// Включение зума колесиком при нажатой клавише Ctrl
	let isCtrlPressed = false;

	window.addEventListener('keydown', function (event) {
		if (event.key === 'Control') {
			isCtrlPressed = true;
			map.scrollWheelZoom.enable();
		}
	});

	window.addEventListener('keyup', function (event) {
		if (event.key === 'Control') {
			isCtrlPressed = false;
			map.scrollWheelZoom.disable();
		}
	});

	map.on('mouseout', function () {
		if (!isCtrlPressed) {
			map.scrollWheelZoom.disable();
		}
	});

	// Убедитесь, что зум изначально отключен
	map.scrollWheelZoom.disable();

	// Функция для открытия попапа маркера по id
	window.openMarkerPopup = function (markerId) {
		const marker = leafletMarkers[markerId];
		if (marker) {
			marker.openPopup(); // Открытие попапа соответствующего маркера
			// центроровать маркер на карте и сместить на 200 пикселей вверх
			map.setView([marker.getLatLng().lat + 1.2, marker.getLatLng().lng], 7); // Центрирование карты на маркер
		} else {
			console.log('Marker not found for markerId:', markerId);
		}
	};
});
