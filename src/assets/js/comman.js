page = $('#wrapper').data('page');
toastr.options.positionClass = 'toast-bottom-right';
function smap(url) {
    const script = document.createElement('script');
    script.textContent = `//# sourceMappingURL=${url}?v=${Date.now()}`;
    document.head.appendChild(script);
    script.remove();
}
function toggleAnimeName() {
    $('.dynamic-name').each(function() {
        var currentName = $(this).text()
          , jName = $(this).data('jname')
          , _this = $(this);
        _this.animate({
            'opacity': 0
        }, 200, function() {
            if (jName.length > 0) {
                _this.text(jName).animate({
                    'opacity': 1
                }, 200);
                _this.data('jname', currentName);

                // Save the state to local storage
                var state = localStorage.getItem('toggleAnimeNameState');
                if (state === null) {
                    state = {};
                } else {
                    state = JSON.parse(state);
                }
                state[_this.data('id')] = !_this.data('isJPName');
                localStorage.setItem('toggleAnimeNameState', JSON.stringify(state));
            }
        });
    })
}
function watchListSubmit(data) {
    if (!loading) {
        loading = true;
        $.post('/src/ajax/anime/wl-up', data, function(res) {
            if (res.redirectTo) {
                window.location.href = res.redirectTo;
            }
            if (res.status) {
                toastr.success(res.msg, 'Success', {
                    timeOut: 5000
                });
                if (res.html) {
                    $('#watch-list-content').html(res.html);
                } else {
                    window.location.reload();
                }
            } else {
                toastr.error(res.msg, '', {
                    timeOut: 5000
                });
            }
            loading = false;
        });
    }
}
$(document).ready(function() {
    // Initialize dropdown functionality
    $('.btn-user-avatar').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).parent().find('.dropdown-menu').toggleClass('show');
    });

    // Close dropdown when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // Prevent dropdown from closing when clicking inside it
    $('.dropdown-menu').click(function(e) {
        e.stopPropagation();
    });

    // Add this CSS dynamically
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .dropdown-menu.show {
                display: block;
                position: absolute;
                transform: translate3d(0px, 38px, 0px);
                top: 0px;
                left: auto;
                right: 0px;
                will-change: transform;
            }
            
            .header_right-user .dropdown-menu {
                min-width: 250px;
                padding: 10px;
                border-radius: 10px;
                border: none;
                box-shadow: 0 0 15px rgba(0,0,0,0.3);
                background: #1e1e1e;
            }

            .dropdown-item {
                color: #ffffff;
                padding: 12px 20px;
                transition: all 0.3s;
                border-radius: 5px;
                margin-bottom: 5px;
            }

            .dropdown-item:hover {
                background: #333333;
                color: #ffffff;
            }

            .dropdown-item i {
                margin-right: 10px;
            }
        `)
        .appendTo('head');

    new Swiper('#slider',{
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '#slider .swiper-pagination',
            clickable: true,
        },
        loop: true,
        autoplay: {
            delay: 3000,
        },
    });
    new Swiper('#trending-home .swiper-container',{
        slidesPerView: 6,
        spaceBetween: 30,
        navigation: {
            nextEl: '.trending-navi .navi-next',
            prevEl: '.trending-navi .navi-prev',
        },
        breakpoints: {
            320: {
                slidesPerView: 3,
                spaceBetween: 2,
            },
            480: {
                slidesPerView: 3,
                spaceBetween: 15,
            },
            900: {
                slidesPerView: 4,
                spaceBetween: 20,
            },
            1320: {
                slidesPerView: 6,
                spaceBetween: 20,
            },
            1880: {
                slidesPerView: 8,
                spaceBetween: 20,
            },
        },
        autoplay: 2000,
    });

    $(".btn-more-desc").click(function(e) {
        $(".film-description .text").toggleClass("text-full");
        $(this).toggleClass("active");
    });
    $("#mobile_search").click(function() {
        $("#mobile_search, #search").toggleClass("active");
    });
    $(".cbox-collapse .btn-showmore").click(function(e) {
        $(this).parent().find(".anif-block-ul").toggleClass("no-limit");
        $(this).toggleClass("active");
    });
    $("#text-home-expand").click(function(e) {
        $(".text-home").toggleClass("thm-expand");
    });

    $(".toggle-basic").click(function(e) {
        $(this).toggleClass("off");
    });
    $(".select-anime-name").click(function() {
        $(".select-anime-name").toggleClass("off"),
        quickSettings("anime_name", $(this).hasClass("off") ? "jp" : "en"),
        toggleAnimeName("all")
    }),
    $(".select-play-dub").click(function() {
        $(".select-play-dub").toggleClass("active"),
        quickSettings("enable_dub", $(this).hasClass("active") ? 1 : 0)
    }),

    $("#turn-off-light").click(function(e) {
        $("#mask-overlay, .anis-watch-wrap").toggleClass("active")
    }),
    $("#mask-overlay").click(function(e) {
        $("#mask-overlay, .anis-watch-wrap").removeClass("active"),
        $("#turn-off-light").removeClass("off")
    }),
   

    $('#contact-form').submit(function(e) {
        e.preventDefault();
        if (!loading) {
            loading = true;
            $('#contact-loading').show();
            $('#contact-error').hide();
            $('#contact-loading').hide();
            loading = false;
            $('#contact-form')[0].reset();
            toastr.success('Thank you! Your message has been submitted and we will get in touch as soon as possible.', '', {
                timeout: 5000
            });
        }
    });

   
});

$(document).on('click', '.dropdown-menu-noti,.dropdown-menu-right', function(e) {
    e.stopPropagation();
});

$(document).on('keyup', '#search-ep', function(e) {
    e.preventDefault();
    var value = e.target.value;
    $('.ep-item').removeClass('highlight');
    if (value) {
        var epEl = $('.ep-item[data-number=' + value + ']');
        if (epEl.length > 0) {
            var parent = epEl.parent();
            $('.ep-page-item[data-page=' + parent.data('page') + ']').click();
            if (e.keyCode === 13) {
                $(e.target).val("");
                epEl.click();
            } else {
                epEl.addClass('highlight');
            }
        }
    } else {
        var currPage = $('.ep-item.active').parent().data('page');
        $('.ep-page-item[data-page=' + currPage + ']').click();
    }
});

$('.f-genre-item').click(function() {
    $(this).toggleClass('active');
    var genreIds = [];
    $('.f-genre-item').each(function() {
        $(this).hasClass('active') && genreIds.push($(this).data('id'));
    })
    $('#f-genre-ids').val(genreIds.join(','));
});

if (Cookies.get('DevTools'))
    Cookies.remove('DevTools');

if ($('.film-description .text').length > 0) {
    var fullDes = $('.film-description .text').html();
    if (fullDes.length > 300) {
        var desShow = fullDes.substring(0, 300) + '...<span class="btn-more-desc more"><i class="fas fa-plus"></i> More</span>'
          , desMore = fullDes.substring(301, fullDes.length);
        $('.film-description .text').html(desShow);
    }
    $(document).on('click', '.btn-more-desc', function() {
        if ($(this).hasClass('more')) {
            $('.film-description .text').html(fullDes + '<span class="btn-more-desc less"><i class="fas fa-minus"></i> Less</span>');
        } else {
            $('.film-description .text').html(desShow);
        }
    });
}

// Add this CSS dynamically for the custom tooltip



  

// Ensure to create the tooltip divs in your HTML for each poster
// Example: <div id="tooltip-1" class="custom-tooltip">Tooltip content here</div>
// Make sure to replace '1' with the appropriate id for each poster


// Handle language toggle
$(function () {
    const langToggles = $('.toggle-lang span');

    function updateLangDisplay(lang) {
        $('.lang-display').text(lang === 'jp' ? 'Japanese' : 'English');
    }

    function applyLanguage() {
        // Set default language to 'en' if not set
        let selectedLang = localStorage.getItem('animeLang');
        if (!selectedLang) {
            selectedLang = 'en';
            localStorage.setItem('animeLang', 'en');
        }

        // Update the language display text
        updateLangDisplay(selectedLang);

        // Toggle 'off' class on the parent element
        $('.select-anime-name.toggle-lang').toggleClass('off', selectedLang === 'jp');

        // Update all dynamic names
        $('.dynamic-name').each(function() {
            const $this = $(this);
            const title = $this.data('title');
            const jname = $this.data('jname');
            $this.stop().fadeTo(250, 0, function() {
                $this.text(selectedLang === 'jp' ? (jname || title) : title);
            }).fadeTo(250, 1);
        });

        // Update active state of language toggles
        langToggles.removeClass('active');
        langToggles.filter('.' + selectedLang).addClass('active');
    }

    // Handle language toggle clicks
    $('.toggle-lang').on('click', 'span', function(e) {
        e.stopPropagation();
        const selectedLang = $(this).hasClass('jp') ? 'jp' : 'en';
        localStorage.setItem('animeLang', selectedLang);
        applyLanguage();
    });

    // Initialize
    applyLanguage();
});

// $(".select-anime-name").click(function () {
//     $(this).toggleClass("off");
//     quickSettings("anime_name", $(this).hasClass("off") ? "jp" : "en");
//     toggleAnimeName("all");
// });


$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

$('div.ui-helper-hidden-accessible[role="log"]').remove();
$('div.ui-helper-hidden-accessible[role="tooltip"]').remove();

document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
  