//  console.log('222')

const swipers = document.querySelectorAll(".news-collection__swiper");

// console.log(swipers);

//  document.querySelectorAll('.news-collection__swiper').forEach(function (container) {

//     console.log(container)

//  });

const swiperTest = new Swiper(".news-collection__swiper", {
  navigation: {
    nextEl: ".news-collection__arrow-next-1",
    prevEl: ".news-collection__arrow-prev-1",
  },
});
