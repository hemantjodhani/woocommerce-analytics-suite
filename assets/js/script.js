jQuery(document).ready(function ($) {

    function get_morning_afternoon_evening_or_night() {
        const currentTime = new Date();
        const currentHour = currentTime.getHours();
    
        if (currentHour >= 0 && currentHour < 12) {
            return "Good morning, ";
        } else if (currentHour < 17) {
            return "Good afternoon, ";
        } else if (currentHour < 20) {
            return "Good evening, ";
        } else {
            return "Good night, ";
        }
    }
    
    $('.was-greet-time').text(get_morning_afternoon_evening_or_night())

    const today = new Date().toISOString().split('T')[0];
    
    $('#from_date, #to_date').attr('max', today);
    
    $('#from_date').on('input', function () {
        const fromDateVal = $(this).val();
        $('#to_date').attr('min', fromDateVal);
    });
    
    $('#to_date').on('input', function () {
        const toDateVal = $(this).val();
        $('#from_date').attr('max', toDateVal);
    });
});

