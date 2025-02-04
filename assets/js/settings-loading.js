jQuery(document).ready(function ($) {
    let isProcessing = false;

    $(document).on('click', '#meeting-time', function (e) {
        e.preventDefault();

        if (isProcessing) return;
        isProcessing = true;

        if (document.querySelector('.wrap-background-grey')) {
            removeWindow();
            isProcessing = false;
            return;
        }

        toggleBackground(true);

        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'admin_get_meetings_time',
                nonce: settingsloadingAjax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $('#ajax-target').html(response.data);
                    setTimeout(()=>dialogOpen(),20);
                } else {
                    console.error('Error:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
            },
            complete: function () {
                isProcessing = false;
            },
        });
    });
    $(document).off('click', '.check-column :checkbox'); 

   $(document).on('click', '#save-settings-days', function (e) {
    e.stopPropagation();
    e.stopImmediatePropagation();

    console.log('saved click');

    const frame = document.querySelectorAll('.am-add-period');
    var parent = document.querySelectorAll('.am-working-hours');

    var times = {};
    let count = 1;

    $('.am-working-hours').each(function () {
        var day = $(this).find('#day').text().trim();
        var starting_time = $(this).find('.starting_time').val();
        var ending_time = $(this).find('.ending_time').val();

        count = countSavedPositions(parent);

        if (starting_time && ending_time) {
            if (!times[day]) {
                times[day] = [];
            }

            times[day].push({
                starting_time: starting_time,
                ending_time: ending_time
            });
        }
    });

    console.log('count', count);
    console.log('times', times);

    if (count && Object.keys(times).length !== 0) {
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_weekly_times',
                nonce: settingsloadingAjax.nonce,
                times: times,
                count: count
            },
            success: function (response) {
                if (response.success) {
                    console.log('frame', frame);
                    frame.forEach(element => element.remove());
                } else {
                    alert('Failed to save times: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error from server:', error);
            }
        });
    }

    const parentDays = $('.am-working-days').find('.am-add-period');
    console.log('target', parentDays);

    const date = parentDays.find('#date-selected').val().trim();
    const dayName = parentDays.find('#name-date-selected').val().trim();

    const lastAddedHours = $('.am-working-days').find('.added-hours').last();
    let countDays = parseInt(lastAddedHours.attr('datasrc'), 10) || 0;

    countDays += 1;
    console.log('Date:', date);
    console.log('dayName', dayName);
    console.log('Count Days:', countDays);

    if (date && countDays) {
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_days_times',
                nonce: settingsloadingAjax.nonce,
                countDays,
                date,
                dayName,
            },
            success: function (response) {
                if (response.success) {
                    console.log('Days saved successfully:', response.data);
                } else {
                    console.error('Failed to save days:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error from server:', error);
            }
        });
    }

    let warning = $(this).closest('.am-working-days').find('.warning-message');
    if (!warning.length) {
        warning = $(document).find('.warning-message');
    }

    if (warning.length) {
        warning.remove();
    }

    removeWindow();
});



    $(document).on('click', '#close-button', removeWindow);
    $(document).on('click', '.close-button', removeWindow);

    $(document).on('click', '#save-settings', function () {
        const durationBok = $('#duration-bok').val();
        const durationOther = $('#duration-other').val();
        const durationOnline = $('#duration-online').val();

        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_meetings_time',
                nonce: settingsloadingAjax.nonce,
                duration_bok: durationBok,
                duration_other: durationOther,
                duration_online: durationOnline,
            },
            success: function (response) {
                if (response.success) {
                    removeWindow();
                } else {
                    alert('Error saving settings.');
                }
            },
            error: function () {
                alert('AJAX request failed.');
            }
        });
    });

    $(document).on('click', '#perspective', function (e) {
        e.preventDefault();

        if (isProcessing) return;
        isProcessing = true;

        if (document.querySelector('.wrap-background-grey')) {
            removeWindow();
            isProcessing = false;
            return;
        }

        toggleBackground(true);

        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'admin_save_calendar_days',
                nonce: settingsloadingAjax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $('#ajax-target').html(response.data);
                    setTimeout(()=>dialogOpen(),20);
                } else {
                    console.error('Error:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
            },
            complete: function () {
                isProcessing = false;
            },
        });
    });

    $(document).on('click', '.el-input-number__decrease', function () {
        const input = $('#duration-calendar');
        let value = parseInt(input.val(), 10) || 365;
        if (value > 1) {
            input.val(value - 1);
        }
    });

    $(document).on('click', '.el-input-number__increase', function () {
        const input = $('#duration-calendar');
        let value = parseInt(input.val(), 10) || 365;
        input.val(value + 1);
    });

    $(document).on('click', '#save-settings-calendar', function () {
        const durationCalendar = parseInt($('#duration-calendar').val(), 10) || 365;

        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_calendar_days',
                nonce: settingsloadingAjax.nonce,
                duration_calendar: durationCalendar,
            },
            success: function (response) {
                if (response.success) {
                    removeWindow();
                } else {
                    alert('Error saving settings.');
                }
            },
            error: function () {
                alert('AJAX request failed.');
            }
        });
    });

    $(document).on('click', '#hours', function (e) {
        e.preventDefault();

        if (isProcessing) return;
        isProcessing = true;

        if (document.querySelector('.wrap-background-grey')) {
            removeWindow();
            isProcessing = false;
            return;
        }

        toggleBackground(true);

        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'admin_set_days',
                nonce: settingsloadingAjax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $('#ajax-target').html(response.data);

                    setTimeout(()=>dialogOpen(),20);

                    setTimeout(()=>openTabs(),500);

                    const addHoursButtons = document.querySelectorAll('.el-icon-plus');

                    if (addHoursButtons) {
                        //console.log('Found buttons:', addHoursButtons);
                    
                        addHoursButtons.forEach((button) => {
                            button.addEventListener('click', () => {
                                if (button.dataset.containerAdded === "true") {
                                    console.log('Container already added for this button. No more can be added.');
                                    return;
                                }
                    
                                const closestContainer = button.closest('.am-working-hours');
                    
                                if (!closestContainer) {
                                    console.error('No .am-working-hours container found for this button');
                                    return;
                                }
                    
                                $.ajax({
                                    url: settingsloadingAjax.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'admin_add_new_window',
                                        nonce: settingsloadingAjax.nonce,
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            //console.log('Appending new content to closest container:', response.data);
                    
                                            closestContainer.insertAdjacentHTML('beforeend', response.data);
                    
                                            button.dataset.containerAdded = "true";

                                            loadTimeOptions();
                                                                                      
                                        } else {
                                            console.error('Error from server:', response.data);
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        console.error('AJAX Error:', status, error);
                                    }
                                });
                            });
                        });
                    }

                } else {
                    console.error('Error:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
            },
            complete: function () {
                isProcessing = false;
            },
        });
    });

    $(document).on('click', '.el-icon-day-plus', function (e) {
        e.preventDefault();
    
        const button = $(this);
    
        const closestContainer = button.closest('.am-working-days');
        if (!closestContainer.length) {
            console.error('No .am-working-days container found for this button');
            return;
        }

        const frame = closestContainer.find('.days-frame');
        if (frame.length === 0) {

            $.ajax({
                url: settingsloadingAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_add_new_date',
                    nonce: settingsloadingAjax.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        console.log('Appending new content to closest container:', response.data);
                        closestContainer.append(response.data);
    
                        $(function () {
                            $("#date-selected").datepicker();
                        });
                    } else {
                        console.error('Error from server:', response.data);
                        alert('Failed to load new date frame. Please try again.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('An error occurred while communicating with the server.');
                }
            });
        } else {
            if (!closestContainer.find('.warning-message').length) {
                closestContainer.append('<div class="warning-message">Proszę wypełnić i zapisać  otwarte okienko danych.</div>');
            }
        }
    });
    


    $(document).on('click', '#save-free-days', function () {
        const button = $(this);
        const parent = button.closest('.am-working-days');
    
        const date = parent.find('#date-selected').val().trim();
        const dayName = parent.find('#name-date-selected').val().trim();
  
        const lastAddedHours = parent.find('.added-hours').last();

        let countDays = parseInt(lastAddedHours.attr('datasrc'), 10) || 0;
    
        countDays += 1;
    
        console.log('parent', parent);
        console.log('Date:', date);
        console.log('Day Name:', dayName);
        console.log('Count:', countDays);
    
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_days_times',
                nonce: settingsloadingAjax.nonce,
                countDays,
                date,
                dayName,
            },
            success: function (response) {
                if (response.success) {
                    const frame = parent.find('.am-add-period');
                    console.log('Days saved successfully:', response.data);
                    frame.remove();

                    $.ajax({
                        url: settingsloadingAjax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'add_days_On_Fly',
                            nonce: settingsloadingAjax.nonce,
                            countDays: countDays,
                        },
                        success: function (response) {
                            if (response.success) {

                                const domTarget = parent;
                                
                                console.log('domTarget', domTarget);
                                
                                if (domTarget) {
                                    $(domTarget).append(response.data);
                                
                                    const addedHours = parent.find('.added-hours');
                                    
                                    if (addedHours.length > 49) {
                                        addedHours.first().remove();
                                    }
                                }
                            } else {
                                alert('Failed to save times: ' + response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error from server:', error);
                        }
                    });
                       
                } else {
                    alert('Failed to save days: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error from server:', error);
            }
        });
    });

    $(document).on('click', '#save-free-days-edit', function () {
        const button = $(this);
        const parent = button.closest('.am-working-days');
        
        const date = parent.find('#date-selected').val().trim();
        const dayName = parent.find('#name-date-selected').val().trim();
    
        const $hourContainer = button.closest('.added-hours');
        const countDays = $hourContainer.attr('datasrc');
    
        console.log('Parent:', parent);

        // First AJAX to save the data
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_days_times',
                nonce: settingsloadingAjax.nonce,
                countDays,
                date,
                dayName,
            },
            success: function (response) {
                if (response.success) {
                    console.log('Days saved successfully:', response.data);
    
                    // Remove the edit form or any additional frames
                    const frame = parent.find('.am-add-period-days');
                    frame.remove();

                    let warning = parent.find('.warning-message');
                    if (!warning.length) {
                        warning = $(document).find('.warning-message');
                    }
                
                    if (warning.length) {
                        warning.remove();
                    }
    
                    // Second AJAX to fetch updated element
                    $.ajax({
                        url: settingsloadingAjax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'add_days_On_Fly',
                            nonce: settingsloadingAjax.nonce,
                            countDays: countDays,
                        },
                        success: function (response) {
                            if (response.success) {
                                const targetElement = parent.find(`.added-hours[datasrc="${countDays}"]`);
                                if (targetElement.length) {
                                    targetElement.replaceWith(response.data);
                                } else {
                                    console.error(`Element with datasrc="${countDays}" not found.`);
                                }
    
                                const addedHours = parent.find('.added-hours');
                                if (addedHours.length > 49) {
                                    addedHours.first().remove();
                                }
                            } else {
                                alert('Failed to save times: ' + response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error from server:', error);
                        }
                    });
                } else {
                    alert('Failed to save days: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error from server:', error);
            }
        });
    });
    
    
    $(document).on('click', '.delete-days', function (e) {
        e.preventDefault();
    
        const $button = $(this);
        const $hourContainer = $button.closest('.added-hours');
        let count = $hourContainer.attr('datasrc');
    
        $hourContainer.remove();
    
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_days_times',
                nonce: settingsloadingAjax.nonce,
                count: count
            },
            success: function (response) {
                if (response.success) {
                    console.log('Time deleted successfully:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });


    $(document).on('click', '.delete-hour', function (e) {
        e.preventDefault();
    
        const $button = $(this);
        const $wrapper = $button.closest('.am-working-hours');
        const $hourContainer = $button.closest('.added-hours');
        const day = $wrapper.find('#day').text().trim(); 
        const count = $hourContainer.index();
    
        console.log('Deleting hour for day:', day, 'Count:', count);
    
        $hourContainer.remove();
    
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_weekly_times',
                nonce: settingsloadingAjax.nonce,
                day: day,
                count: count
            },
            success: function (response) {
                if (response.success) {
                    console.log('Time deleted successfully:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });

    $(document).on('click', '.edit-hour', function (e) {
        e.preventDefault();
    
        const button = $(this);
        const closestContainer = button.closest('.am-working-hours');
    
        const timeRange = closestContainer.find('.added-hours').text().trim();
    
        const times = timeRange.match(/\d{2}:\d{2} - \d{2}:\d{2}/);
    
        if (times) {
            $.ajax({
                url: settingsloadingAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_add_edit_window',
                    nonce: settingsloadingAjax.nonce,
                    times: times[0]
                },
                success: function (response) {
                    if (response.success) {
                        closestContainer.append(response.data);
                        button.data('containerAdded', false);
                        loadTimeOptions();
                    } else {
                        console.error('Server error:', response.data);
                        alert('Failed to load the edit window. Please try again.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('An error occurred while communicating with the server.');
                }
            });
        } else {
            console.error('Invalid time format:', timeRange);
            alert('Invalid time format received.');
        }
    });

    $(document).on('click', '.edit-days', function (e) {
        e.preventDefault();
    
        const button = $(this);
        const closestContainer = button.closest('.added-hours');
        let count = closestContainer.attr('datasrc');

        console.log('count edit',count);
    
        if (count) {
            $.ajax({
                url: settingsloadingAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_add_edit_day_window',
                    nonce: settingsloadingAjax.nonce,
                    count: count
                },
                success: function (response) {
                    if (response.success) {
                        closestContainer.append(response.data);
                    } else {
                        console.error('Server error:', response.data);
                        alert('Failed to load the edit window. Please try again.');
                    }

                    $( function() {
                        $( "#date-selected" ).datepicker();
                    } );
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('An error occurred while communicating with the server.');
                }
            });
        }
    });

    $(document).on('click', '#save-edited-hours', function(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
    
       
        const frame = document.querySelectorAll('.am-add-period');
        var parent = document.querySelectorAll('.am-working-hours');
    
        var times = {};
        let count = 1;
        
        $('.am-working-hours').each(function() {
            var day = $(this).find('#day').text().trim();
            var starting_time = $(this).find('.starting_time').val();
            var ending_time = $(this).find('.ending_time').val();
    
            count = countEditedPositions(parent);
    
            if (starting_time && ending_time) {
                if (!times[day]) {
                    times[day] = [];
                }
    
                times[day].push({
                    starting_time: starting_time,
                    ending_time: ending_time
                });
            }
        });
    
        console.log('count', count);
        console.log('times', times);
    
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_weekly_times',
                nonce: settingsloadingAjax.nonce,
                times: times,
                count: count
            },
            success: function(response) {
                if (response.success) {
                    console.log('frame', frame);
                    frame.forEach(element => element.remove());
    
                    removeWindow();
                } else {
                    alert('Failed to save times: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error from server:', error);
            }
        });
    });
    
    
    $(document).on('click', '#hours-cancel-button', function () {
        const frame = $(this).closest('.am-add-period');
        if (frame.length) {
            frame.remove();
            $('.edit-hour').data('containerAdded', false);
        } 
    });

    $(document).on('click', '#days-cancel-button', function () {
        
        const frame = $(this).closest('.am-add-period-days');
        if (frame.length) {
            frame.remove();
        } 
    

        let warning = $(this).closest('.am-working-days').find('.warning-message');
        if (!warning.length) {
            warning = $(document).find('.warning-message');
        }
    
        if (warning.length) {
            warning.remove();
        }
    });
    
    $(document).on('click', '#save-settings-hours', function () {
        const button = $(this);
        const frame = button.closest('.am-add-period');
        const parent = button.closest('.am-working-hours');
    
        if (!parent.length || !frame.length) {
            console.error('Required elements are missing');
            return;
        }
    
        let times = {};
        let count = 1;
    

        $('.am-working-hours').each(function () {
            const day = $(this).find('#day').text().trim();
            const starting_time = $(this).find('.starting_time').val();
            const ending_time = $(this).find('.ending_time').val();
    
            count = countPositions(parent);
    
            if (starting_time && ending_time) {
                if (!times[day]) {
                    times[day] = [];
                }
                times[day].push({
                    starting_time,
                    ending_time,
                });
            }
        });
    
        $.ajax({
            url: settingsloadingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_weekly_times',
                nonce: settingsloadingAjax.nonce,
                times,
                count,
            },
            success: function (response) {
                if (response.success) {
                    frame.remove();
                    button.data('containerAdded', false);
    
                    $.ajax({
                        url: settingsloadingAjax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'add_hours_On_Fly',
                            nonce: settingsloadingAjax.nonce,
                            times,
                            count,
                        },
                        success: function (response) {
                            if (response.success) {
                                const target = parent.closest('.am-working-hours');
                                const domTarget = target.find('.am-dialog-table').get(0);
    
                                if (domTarget) {
                                    $(domTarget).append(response.data);
    
                                    const addedHours = target.find('.added-hours');
                                    if (addedHours.length > 3) {
                                        addedHours.first().remove();
                                        button.data('containerAdded', false);
                                    }
                                }
                            } else {
                                alert('Failed to save times: ' + response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error from server:', error);
                        }
                    });
                } else {
                    alert('Failed to save times: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error from server:', error);
            }
        });
    });
    
    function removeWindow() {
        const background = document.querySelector('.wrap-background-grey');

        if (background) {
            const window = document.querySelector('.window-wrap');
            if (window) {
                window.classList.remove('window-wrap-ajax-left');
                setTimeout(() => {
                    window.remove();
                    toggleBackground(false);
                }, 1000); 
                background.classList.remove('wrap-background-grey');
                background.classList.add('wrap-background-white'); 
            }
        }
    }

    function dialogOpen() {
        const window = document.querySelector('.window-wrap');
        if (window) {
            window.classList.add('window-wrap-ajax');
            window.classList.add('window-wrap-ajax-left');
        }
    }

    function toggleBackground(toGrey = true) {
        const background = document.querySelector('.wrap-background-white');
        if (background) {
            background.classList.toggle('wrap-background-grey', toGrey);
            background.classList.toggle('wrap-background-white', !toGrey);
        }
    }


    function openTabs() {

        const tabButtons = document.querySelectorAll('.tablinks');
        const tabContents = document.querySelectorAll('.tabcontent');
        tabContents.forEach((content) => {
            content.style.display = 'none';
        });
        const defaultActiveTab = document.querySelector('.tablinks.active');
        if (defaultActiveTab) {
            const tabId = defaultActiveTab.getAttribute('data-tab');
            const defaultTabContent = document.getElementById(tabId);
            if (defaultTabContent) {
                defaultTabContent.style.display = 'block';
            }
        }
    
        tabButtons.forEach((button) => {
            button.addEventListener('click', (e) => {
                const tabId = button.getAttribute('data-tab');
    
                tabContents.forEach((content) => {
                    content.style.display = 'none';
                });
    
                tabButtons.forEach((btn) => {
                    btn.classList.remove('active');
                });
    
                const tabToShow = document.getElementById(tabId);
                if (tabToShow) {
                    tabToShow.style.display = 'block';
                }
    
                button.classList.add('active');
            });
        });
    }

    function loadTimeOptions() {
        console.log('loaded');
        const timeOptions = [
            "00:00", "00:30", "01:00", "01:30", "02:00", "02:30", "03:00", "03:30",
            "04:00", "04:30", "05:00", "05:30", "06:00", "06:30", "07:00", "07:30",
            "08:00", "08:30", "09:00", "09:30", "10:00", "10:30", "11:00", "11:30",
            "12:00", "12:30", "13:00", "13:30", "14:00", "14:30", "15:00", "15:30",
            "16:00", "16:30", "17:00", "17:30", "18:00", "18:30", "19:00", "19:15", 
            "19:30", "20:00", "20:30", "21:00", "21:30", "22:00", "22:30", "23:00", 
            "23:30", "24:00"
        ];
        
        function appendTimeOptions(targetElement, type, wrapper) {
            console.log('appended');
    
            if (!wrapper) {
                wrapper = document.createElement('div');
                wrapper.classList.add('time-input-wrapper');
                targetElement.parentElement.appendChild(wrapper);
            }
    
            const existingContainer = wrapper.querySelector('.time-select-container');
            if (existingContainer) {
                existingContainer.remove();
            }
    
            const timeSelectContainer = document.createElement('div');
            timeSelectContainer.classList.add('time-select-container');
    
            const timeRowContainer = document.createElement('div');
            timeRowContainer.classList.add('time-row');
    
            const filteredTimes = getFilteredTimes(type, wrapper);
    
            filteredTimes.forEach((time) => {
                const timeItem = document.createElement('div');
                timeItem.classList.add('time-select-item');
                timeItem.textContent = time;
                timeRowContainer.appendChild(timeItem);
            });
    
            timeSelectContainer.appendChild(timeRowContainer);
    
            const cancelButton = document.createElement('button');
            cancelButton.textContent = "Cancel";
            cancelButton.classList.add('cancel-button');
            cancelButton.addEventListener('click', function () {
                timeSelectContainer.remove();
                targetElement.value = '';
            });
    
            timeSelectContainer.appendChild(cancelButton);
            wrapper.appendChild(timeSelectContainer);
    
            timeRowContainer.querySelectorAll('.time-select-item').forEach(item => {
                item.addEventListener('click', function () {
                    targetElement.value = item.textContent;
                    targetElement.setAttribute('value', item.textContent);
                    console.log('value', targetElement.value );
                    timeSelectContainer.remove();
                });
            });
    
            return timeSelectContainer;
        }
    
        function getFilteredTimes(type, wrapper) {

            const parent = wrapper.closest('.timetable-container');
            
            if (!parent) {
                console.error('Parent container not found');
                return timeOptions;
            }

            const startingTimeElement = parent.querySelector('.starting_time');
            const endingTimeElement = parent.querySelector('.ending_time');
        
            console.log('Parent element:', parent);
            console.log('Starting Time Element:', startingTimeElement);
            console.log('Ending Time Element:', endingTimeElement);
        
            if (!startingTimeElement || !endingTimeElement) {
                console.warn('Starting or Ending time elements are not fully initialized yet.');
                return timeOptions;
            }
        
            const startingTime = startingTimeElement.value.trim();
            const endingTime = endingTimeElement.value.trim();
        
            console.log('Starting Time:', startingTime);
            console.log('Ending Time:', endingTime);
        
            if (type === 'starting') {
                if (endingTime) {
                    const startIndex = timeOptions.indexOf(endingTime);
                    return timeOptions.slice(0, startIndex);
                }
                return timeOptions;
            }
        
            if (type === 'ending') {
                if (startingTime) {
                    const endIndex = timeOptions.indexOf(startingTime);
                    return timeOptions.slice(endIndex + 1);
                }
                return timeOptions;
            }
        
            return timeOptions;
        }
        
        function initializeTimeInputs() {
            const startingTimeInputs = document.querySelectorAll('.starting_time');
            const endingTimeInputs = document.querySelectorAll('.ending_time');
        
            startingTimeInputs.forEach(startingTimeInput => {
                startingTimeInput.addEventListener('focus', function () {
                    const wrapper = startingTimeInput.closest('.time-container');
                    appendTimeOptions(startingTimeInput, 'starting', wrapper);
                });
        
                startingTimeInput.addEventListener('input', function () {
                    const wrapper = startingTimeInput.closest('.time-container');
                    appendTimeOptions(startingTimeInput, 'starting', wrapper);
                });
            });
        
            endingTimeInputs.forEach(endingTimeInput => {
                endingTimeInput.addEventListener('focus', function () {
                    const wrapper = endingTimeInput.closest('.time-container');
                    appendTimeOptions(endingTimeInput, 'ending', wrapper);
                });
        
                endingTimeInput.addEventListener('input', function () {
                    const wrapper = endingTimeInput.closest('.time-container');
                    appendTimeOptions(endingTimeInput, 'ending', wrapper);
                });
            });
        }
    
        initializeTimeInputs();
    }
    
    function countPositions(wrapper) {
        console.log('wrapper',wrapper);
        const countCheck = wrapper.find('.added-hours').length;
        let count = 1;

        console.log('count',countCheck)

        if (countCheck === 1) {
            count = 2;
        } else if (countCheck === 2) {
            count = 3;
        } else if (countCheck === 3) {
            count = 1;
        }

        return count;
    }

    function countSavedPositions(wrapper) {
        console.log('wrapper', wrapper);
    
        let count = 1;
        if (!wrapper || !$(wrapper).length) {
            console.error('Invalid wrapper passed to countSavedPositions');
            return count; 
        }
    
        try {
            $(wrapper).each(function () {
                const $this = $(this);
                const countCheck = $this.find('.added-hours').length;
    
                console.log('countCheck for this .am-working-hours', countCheck);
    
                if (countCheck === 1) {
                    count = 2;
                } else if (countCheck === 2) {
                    count = 3;
                } else if (countCheck === 3) {
                    count = 1;
                }
            });
        } catch (error) {
            console.error('Error in countSavedPositions:', error);
        }
    
        console.log('Final count:', count);
        return count;
    }

    function countEditedPositions(wrapper) {
        console.log('wrapper', wrapper);
    
        let count = 1;
        if (!wrapper || !$(wrapper).length) {
            console.error('Invalid wrapper passed to countSavedPositions');
            return count; 
        }
    
        try {
            $(wrapper).each(function () {
                const $this = $(this);
                const countCheck = $this.find('.added-hours').length;
    
                console.log('countCheck for this .am-working-hours', countCheck);
    
                if (countCheck === 1) {
                    count = 1;
                } else if (countCheck === 2) {
                    count = 2;
                } else if (countCheck === 3) {
                    count = 3;
                }
            });
        } catch (error) {
            console.error('Error in countSavedPositions:', error);
        }
    
        console.log('Final count:', count);
        return count;
    }
    
    
});


