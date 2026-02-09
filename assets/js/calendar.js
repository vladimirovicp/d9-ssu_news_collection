const initCalendar = (elem) => new AirDatepicker(elem, {
  range: true, // разрешить выделять диапазон
  multipleDatesSeparator: ' - ', // разделитель дат в диапазоне 
  keyboardNav: true, // навигация с клавитатуры: разрешить
  onSelect({ date, formattedDate, datepicker }) { // на onSelect нужно стриггерить input событие при выделении даты
    const triggerEvent = (el, eventType, detail) =>
      el.dispatchEvent(new Event(eventType)); // создаем и отправляем событие 
    triggerEvent(datepicker.$el, 'input'); // триггерим созданный эвент на календаре
  },
  
  //inline: true,


});


const airDatepickers = document.querySelectorAll('.news-collection__air-datepicker');

if( airDatepickers.length > 0 ){
    airDatepickers.forEach((airDatepicker, index) => {
        airDatepicker.classList.add(`air-datepicker${index+1}`);
        initCalendar(`.air-datepicker${index+1}`);
    }); 
}


// const calendar = initCalendar('.news-calendar');
// const calendar2 = initCalendar('.news-calendar2');

let dates = [] // создаем переменную для хранения информации о датах
const date_min = document.querySelector('input[name="field_publ_date_value[min]"]')
const date_max = document.querySelector('input[name="field_publ_date_value[max]"]')




/**
 * filterDates сравнивает дату публикации с диапазоном, заданным в условии
 * Возвращает отфильтрованный массив
 * @param {publ_date} - Дата публикации
 * @param {dates} - Диапазон из условия
 */
function filterDates(publ_date, dates) {
  const createDate = (string) => new Date(+string.split('.')[2], +string.split('.')[1] - 1, +string.split('.')[0])
  const date = createDate(publ_date)
  if (dates.includes('-')) { // если диапазолн дат
    let date_min = createDate(dates.split(' ')[0]), date_max = createDate(dates.split(' ')[2])
    return date > date_min && date < date_max
  }
  else { // усли одна дата
    return publ_date == dates
  }
}

/**
 * filterSelectCategory сравнивает текстовые поля с условием
 * Возвращает отфильтрованный массив
 * @param {targetHeading} - Текст новости
 * @param {targetKeywords} - Заголовок
 * @param {word} - строка для поиска
 */
function filterSelectCategory(targetHeading, targetKeywords, word) {
  return targetHeading.search(word) != -1 || targetKeywords.search(word) != -1
}


/**
 * filterTextFields сравнивает текстовые поля с условием
 * Возвращает отфильтрованный массив
 * @param {targetText} - Текст новости
 * @param {targetTitle} - Заголовок
 * @param {string} - строка для поиска
 */
function filterTextFields(targetText, targetTitle, string) {
  return targetTitle.search(string) != -1 || targetText.search(string) != -1
}


/**
 * filterBy принимает условия поиска и массив, по которому нужно произвести фильтрацию
 * Возвращает отфильтрованный массив
 * @param {data} - Оригинальный массив
 * @param {cond} - Условия поиска в виде объекта
 */
function filterBy(data, cond) {
  
  if (cond.dates == '' && cond.string == '' && cond.category == '') {
    return data
  }
  return data.filter((row) => filterDates(row.field_publ_date.replace(/  |\r\n|\n|\r/gm, ''), cond.dates)
    && filterTextFields(row.field_news_paragraphs.replace(/  |\r\n|\n|\t|\r/gm, '').toLowerCase(),
      row.title.replace(/  |\r\n|\n|\r/gm, '').toLowerCase(), cond.string) && filterTextFields(row.field_heading, row.field_news_keywords, cond.category));
}


/**
 * pullArrayMedia создает nodeobjecs для массива медиа
 *  Возвращает node object'ы в массиве
 * @param {sliderItems} - Массив ссылок на медиа
 */
function pullArrayMedia(mediaArray) {
  let sliderItems = []
  for (let i = 0; i <= mediaArray.length - 1; i++) {
    let img = document.createElement("img"), slide = document.createElement("div");
    img.setAttribute('class', 'news-header__image')
    img.setAttribute('src', 'http://10.0.2.17:8882/sites/default/files/styles/large/public/node/news/header/' + mediaArray[i])
    slide.setAttribute('class', 'swiper-slide')
    slide.appendChild(img)
    sliderItems.append(slide)
  }
  return sliderItems
}

/**
 * createMarkup создает разметку для новости
 *  Возвращает node object
 * @param {item} - Отфильтрованный массив новостей
 */
function createMarkup(item) {
  const gridItem = document.createElement("div"), images = document.createElement("div"),
    slider = document.createElement("div"), announce = document.createElement("div"),
    announce_top = document.createElement("div"), date = document.createElement("div"),
    title = document.createElement("div"), text = document.createElement("div"),
    sliderWrapper = document.createElement("div"), img = document.createElement("img"),
    tags = document.createElement("div");
  gridItem.setAttribute("class", 'news-feed__grid-item')
  images.setAttribute("class", 'news-feed__image')
  announce.setAttribute("class", 'news-feed__announce')
  announce_top.setAttribute("class", 'news-feed__announce-top')
  slider.setAttribute('class', 'news-header__slider')
  slider.classList.add('swiper')
  img.setAttribute('class', 'news-header__image')
  sliderWrapper.setAttribute('class', 'swiper-wrapper')
  date.setAttribute("class", 'news-feed__date')
  title.setAttribute("class", 'news-feed__title')
  text.setAttribute("class", 'news-feed__announce-text')
  tags.setAttribute('class', 'news-feed__tags')
  date.textContent = item.field_publ_date ? item.field_publ_date + ' / ' + item.field_publ_date : ''
  title.textContent = item.title ? item.title : ''
  text.textContent = item.field_news_paragraphs.substring(0, 700) ? item.field_news_paragraphs.substring(0, 700) : ''
  tags.textContent = item.field_heading
  let sliderItems = []
  if (item.field_news_header_slider.split(',').length > 1) {
     sliderItems = pullArrayMedia(item.field_news_header_slider.split(','))
  }
  else {
    img.setAttribute('src', 'http://10.0.2.17:8882/sites/default/files/styles/large/public/node/news/header/' + item.field_news_header_slider)
    sliderItems = img
  }
  sliderWrapper.append(sliderItems)
  announce_top.append(date, tags)
  announce.append(announce_top, title, text)
  gridItem.appendChild(images)
  gridItem.appendChild(announce)
  slider.appendChild(sliderWrapper)
  images.appendChild(slider)
  return gridItem
}

/**
 * showItems принимает готовый массив и выводит его на странице
 * @param {array} - Отфильтрованный массив новостей
 */
function showItems(array) {
  const newsGrid = document.querySelector('.news-feed__grid')
  if (newsGrid) {
    newsGrid.classList.add('collapsed')
    while (newsGrid.firstChild) { // в цикле удаляем все новости из сетки
      newsGrid.removeChild(newsGrid.firstChild);
    }

    for (let i = 0; i < array.length; i++) {
      newsGrid.append(createMarkup(array[i]))
    }

    newsGrid.classList.remove('collapsed')
  }
}




function getSelected(title, dates, category) {
  const response = fetch("../json/feed.json") // fetch json
    .then((response) => response.json())
    .then((array) => {
      let cond = { // массив условий поиска
        string: title.toLowerCase(),
        dates: dates,
        category: category,
      }

      let filtered = filterBy([...new Set(array)], cond)
      console.log(filtered)
      console.log(cond)
      showItems(filtered)
    })
}


window.onload = function () { // входим в фильтр, когда окно прогрузилось
  let submitFilters = document.getElementById('news-search-form') // хватаем форму с фильтрами
  if (submitFilters) { // если элемент содержится на странице
    submitFilters.addEventListener('submit', (e) => { // добавить прослушиватель на клик
      e.preventDefault(); // предотвратить обновление страницы после клика на submit
      const form = e.target;
      const formFields = form.elements;
      const dates = formFields.date_search.value;
      const category = formFields.category_search.value == 'none' ? '' : formFields.category_search.value;
      const title = formFields.string_search.value ? formFields.string_search.value : ''
      getSelected(title, dates, category)
    })
  }
}



// Drupal.behaviors.fetchNews = {
//   attach: function (context, settings) {
//     once('fetchNews', 'html').forEach(function (element) {
//       logNews();
//     })
//   }
// }