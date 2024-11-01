(function($) {
    $(function() {
        $.widget("zpd.paging", {
            options: {
                limit: 40,
                rowDisplayStyle: 'block',
                activePage: 0,
                rows: []
            },
            _create: function() {
                var rows = $("tbody", this.element).children();
                this.options.rows = rows;
                this.options.rowDisplayStyle = rows.css('display');
                var nav = this._getNavBar();
                this.element.after(nav);
                var _limit = [10, 20, 30, 50, 100];

                for (var i = 0; i <_limit.length ; i++) {
                    this._on($('<option>', {
                        value: _limit[i],
                        text: _limit[i],
                    }).appendTo($(".limit_box")),
                            {click: ""});
                }
                this.showPage(0);
            },
            _getNavBar: function() {
                var rows = this.options.rows;
                var nav = $('<div>', {class: 'paging-nav'});
                 this._on($('<span>', {
                        text: 'Page',
                        'class':'gawd_page_separator'
                    }).appendTo(nav),
                            {change: ""});
                this._on($('<input>', {
                        type: 'text',
                        value: 1,
                        "data-page": (0)
                    }).appendTo(nav),
                            {change: "pageClickHandler"});

                this._on($('<span>', {
                        text: '',
                        'class':'gawd_page_count'
                    }).appendTo(nav),
                          {change: ""});

                /*for (var i = 0; i < Math.ceil(rows.length / this.options.limit); i++) {
                    this._on($('<a>', {
                        href: '#',
                        text: (i + 1),
                        "data-page": (i)
                    }).appendTo(nav),
                            {click: "pageClickHandler"});
                }*/
                //create previous link
                this._on($('<a>', {
                    href: '#',
                    text: '',
                    "data-direction": -1
                }).prependTo(nav),
                        {click: "pageStepHandler"});
                //create next link
                this._on($('<a>', {
                    href: '#',
                    text: '',
                    'class':'gawd_next_link',
                    "data-direction": +1
                }).appendTo(nav),
                        {click: "pageStepHandler"});

                 //create first link
                this._on($('<a>', {
                    href: '#',
                    text: '',
                    "data-direction": -1,
                    "class": "first_link"
                }).prependTo(nav),
                        {click: "pageLastFirstHandler"});
                //create last link
                this._on($('<a>', {
                    href: '#',
                    text: '',
                    "data-direction": +1,
                    "class": "last_link"
                }).appendTo(nav),
                        {click: "pageLastFirstHandler"});
      // limit box
                this._on($('<select>', {
                        value: 10,
                        "class": "limit_box"
                    }).appendTo(nav),
                        {change: "setLimit"});

                return nav;
            },
            showPage: function(pageNum) {
                var num = pageNum * 1; //it has to be numeric
                this.options.activePage = num;
                var rows = this.options.rows;
                var limit = this.options.limit;
                var page_of = 'of ' +  Math.ceil(Number(rows.length/limit));
                jQuery('.gawd_page_count').text(page_of);
                for (var i = 0; i < rows.length; i++) {
                    if (i >= limit * num && i < limit * (num + 1)) {
                        $(rows[i]).css('display', this.options.rowDisplayStyle);
                    } else {
                        $(rows[i]).css('display', 'none');
                    }
                }
            },
            pageClickHandler: function(event) {
                event.preventDefault();
                //$(event.target).siblings().attr('class', "");
                //$(event.target).attr('class', "selected-page");
                var rowsLength = this.options.rows.length/4;
                var pageNum = Number($(event.target).val()) - 1;
                var limit = this.options.limit/4;
                if (pageNum*limit > rowsLength) {
                  $(".last_link").click();
                  return false;
                }
                else if (pageNum<1) {
                  $(".first_link").click();
                  return false;
                }
                this.showPage(pageNum);
            },
            pageStepHandler: function(event) {
                event.preventDefault();
                //get the direction and ensure it's numeric
                var dir = $(event.target).attr('data-direction') * 1;
                var pageNum = this.options.activePage + dir;
                //if we're in limit, trigger the requested pages link
                var rowsLength = this.options.rows.length/4;
                var limit = this.options.limit/4;
                if (pageNum >= 0 && pageNum*limit < rowsLength) {
                    //$("a[data-page=" + pageNum + "]", $(event.target).parent()).click();
                    $("input[data-page]").val(pageNum + 1);
                    this.showPage(pageNum);
                }
            },
            pageLastFirstHandler: function(event) {
                event.preventDefault();
                //get the direction and ensure it's numeric
                var dir = $(event.target).attr('data-direction') * 1;
                if(dir == -1){
                  var pageNum = 0;
                  $("input[data-page]").val(1);
                  this.showPage(pageNum);
                }
                else{
                  var rowsLength = this.options.rows.length/4;
                  var limit = this.options.limit/4;
                  var pageNum = Math.ceil(rowsLength/limit);
                  $("input[data-page]").val(pageNum);
                  this.showPage(pageNum-1);
                }
            },
            setLimit: function(event) {
              event.preventDefault();

              this.options.limit = Number($(event.target).val())*4;
              this.showPage(0);
            },


        });
    });
})(jQuery);