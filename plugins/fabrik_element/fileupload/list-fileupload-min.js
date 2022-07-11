/*! Fabrik */

define(["jquery"],function(e){return new Class({options:{isCarousel:!1},Implements:[Events,Options],initialize:function(s,i){this.setOptions(i),this.options.isCarousel&&(e(".slickCarousel").slick(),e(".slickCarouselImage").css("opacity","1"))}})});