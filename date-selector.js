(function($) {
    $(document).ready(function() {
        var calendar = $('#date-selector .date-selector-calendar');

        calendar.waypoint(function(event, direction) {
            //alert('foo');
            $(this).toggleClass('sticky', direction === 'down');
            event.stopPropagation();
        });

        calendar.localScroll();
    });
})(jQuery);
