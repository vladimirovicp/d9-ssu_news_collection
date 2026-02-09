      
        const sectionNewsCollections = document.querySelectorAll('.news-collection__pagination');

        if (sectionNewsCollections.length > 0) { 

            //Start Кнопка открыть фильтр и закрыть фильтр
            sectionNewsCollections.forEach( (sectionNewsCollection) => {

            
                const openBtn = sectionNewsCollection.querySelector('.news-collection__filter-btn');

                if(openBtn){
                    const filterModal = sectionNewsCollection.querySelector('.news-collection__filter-modal');
                    const closeBtn = filterModal.querySelector('.news-collection__filter-btn');
    
                    closeBtn.addEventListener('click', function () {
                        filterModal.classList.remove('_active');
                    });
    
                    openBtn.addEventListener('click', function () {
                        filterModal.classList.add('_active');
                    })
                }



            });
            //End Кнопка открыть фильтр и закрыть фильтр

            //Start input текущая страница
            sectionNewsCollections.forEach((sectionNewsCollection, index) => {
                const paginationTwo = sectionNewsCollection.querySelector('.news-collection__pagination-two');
                const newPage = paginationTwo.querySelector('.news-collection__pagination-input');
                const pageMax = newPage.max;
                const prevLink = sectionNewsCollection.querySelector('.news-collection__pagination-arrow-prev');
                const nextLink = sectionNewsCollection.querySelector('.news-collection__pagination-arrow-next');

                newPage.addEventListener('keydown', (e) => {
                    if (e.key === '-') {
                        e.preventDefault(); // Блокируем ввод символа '-'
                    }
                });

                newPage.addEventListener('blur', () => {
                    if (parseInt(newPage.value, 10) >= pageMax) {
                        newPage.value = pageMax - 1;
                    }

                    if(parseInt( pageMax == 1)){
                        newPage.value = pageMax;
                    }

                });

                newPage.addEventListener('input', function (event) {
                    let goPage = event.target.value;
                    if (Number(goPage) >= Number(pageMax)) {
                        goPage = pageMax - 1;
                    }

                    if(parseInt( pageMax == 1)){
                        goPage = pageMax;
                    }

                    let href = prevLink.href;
                    const positionPageStart = Number(href.indexOf('?page=')) + 6;
                    const positionEnd = Number(href.indexOf('&'));
                    let newLinkStart = href.substring(0, positionPageStart);
                    let newLinkEnd = positionEnd === -1 ? '' : href.substring(positionEnd);

                    prevLink.href = newLinkStart + String(Number(goPage) - 1) + newLinkEnd;
                    nextLink.href = newLinkStart + String(Number(goPage) + 1) + newLinkEnd;

                    const otherNumberPagination = index === 0 ? 1 : 0;
                    const newPageTwo = sectionNewsCollections[otherNumberPagination].querySelector('.news-collection__pagination-input');
                    newPageTwo.value = goPage;

                    const prevLinkTwo = sectionNewsCollections[otherNumberPagination].querySelector('.news-collection__pagination-arrow-prev');
                    const nextLinkTwo = sectionNewsCollections[otherNumberPagination].querySelector('.news-collection__pagination-arrow-next');

                    prevLinkTwo.href = newLinkStart + String(Number(goPage) - 1) + newLinkEnd;
                    nextLinkTwo.href = newLinkStart + String(Number(goPage) + 1) + newLinkEnd;

                 });

            });
            //End input текущая страница
        }
