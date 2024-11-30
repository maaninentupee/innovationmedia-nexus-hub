/**
 * Live-haku
 */
(function($) {
    'use strict';

    // Hakukentän viive (ms)
    const SEARCH_DELAY = 500;
    let searchTimer;

    // Alusta live-haku
    function initLiveSearch() {
        const $searchForm = $('.search-form');
        const $searchInput = $searchForm.find('.search-field');
        const $resultsContainer = $('<div class="live-search-results"></div>');

        // Lisää tuloskontti
        $searchForm.append($resultsContainer);

        // Käsittele hakukentän muutokset
        $searchInput.on('input', function() {
            const term = $(this).val();
            clearTimeout(searchTimer);

            if (term.length < 3) {
                $resultsContainer.empty().hide();
                return;
            }

            // Odota kunnes käyttäjä lopettaa kirjoittamisen
            searchTimer = setTimeout(() => {
                performSearch(term, $resultsContainer);
            }, SEARCH_DELAY);
        });

        // Piilota tulokset kun klikataan muualle
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-form').length) {
                $resultsContainer.empty().hide();
            }
        });
    }

    // Suorita haku
    function performSearch(term, $container) {
        $.ajax({
            url: liveSearchParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'live_search',
                nonce: liveSearchParams.nonce,
                term: term
            },
            beforeSend: function() {
                $container.html('<div class="searching">Haetaan...</div>').show();
            },
            success: function(response) {
                if (response.success && response.data.length) {
                    displayResults(response.data, $container);
                } else {
                    $container.html('<div class="no-results">Ei hakutuloksia</div>');
                }
            },
            error: function() {
                $container.html('<div class="error">Virhe haussa</div>');
            }
        });
    }

    // Näytä hakutulokset
    function displayResults(results, $container) {
        $container.empty();

        const $list = $('<ul class="live-search-list"></ul>');
        
        results.forEach(result => {
            const $item = $(`
                <li class="search-result-item">
                    ${result.thumbnail ? `
                        <div class="result-thumbnail">
                            <img src="${result.thumbnail}" alt="${result.title}">
                        </div>
                    ` : ''}
                    <div class="result-content">
                        <h4><a href="${result.url}">${result.title}</a></h4>
                        <p>${result.excerpt}</p>
                    </div>
                </li>
            `);
            $list.append($item);
        });

        $container.append($list).show();
    }

    // Alusta kun DOM on valmis
    $(document).ready(initLiveSearch);

})(jQuery);
