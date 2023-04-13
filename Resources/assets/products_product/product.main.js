/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

/** Символьный код */
/* Получаем поле, Согласно выбранной локали */
let $name = document.querySelector("input[data-lang='product_form_trans_" + $lang + "']");

if ($name) {

    setTimeout(function initCatUrl() {

        if (typeof catUrl.debounce == 'function') {

            $name.addEventListener('input', catUrl.debounce(500));
            return;
        }

        setTimeout(initCatUrl, 100);

    }, 100);

    function catUrl() {
        /* Заполняем транслитом URL */
        document.getElementById('product_form_info_url').value = translitRuEn(this.value).toLowerCase();
    }
}



const searcOffer = document.getElementById('searcherOffer');
let $ul = document.getElementById("searcher-offer");

searcOffer.addEventListener('keyup', searcherOffer);
searcOffer.addEventListener('focus', searcherOffer);

$ul.querySelectorAll('li').forEach(function (item) {

    searcOffer.classList.remove('d-none');

    item.addEventListener('click', function () {
        let inpt = document.getElementById(item.dataset.href);
        inpt.scrollIntoView({block: "center", inline: "center", behavior: "smooth"});
        $ul.classList.remove('show');
        setTimeout(function () {
            inpt.focus();
        }, 200);
    });
});

function searcherOffer() {

    let $filter = this.value.toUpperCase();
    let $counter = 0;

    $ul.querySelectorAll('li').forEach(function (item) {

        let txtValue = item.textContent || item.innerText;

        if (txtValue.toUpperCase().indexOf($filter) > -1) {
            item.style.display = "";
            $counter++;
        } else {
            item.style.display = "none";
        }

        if ($filter.length < 2 || $counter === 0) {
            $ul.classList.remove('show');
        } else {
            $ul.classList.add('show');
        }
    });
}