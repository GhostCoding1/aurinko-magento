/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    'jquery',
	'rokanthemes/lazyloadimg'
], function ($) {
    'use strict';
	$(window).load(function() {
		$('body').addClass('preloaded');
		$('#back-top').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 800);
			return false;
		});
	});
	$(document).ready(function () {
		$("img.lazy").lazyload({
			skip_invisible: false
		});
	});
    var scrolled_back = false;
    $(window).scroll(function () {
		if ($(this).scrollTop() > 100 && !scrolled_back) {
			$('#back-top').fadeIn();
			scrolled_back = true;
		}
		if ($(this).scrollTop() <= 100 && scrolled_back) {
			$('#back-top').fadeOut();
			scrolled_back = false;
		}
	});
});
