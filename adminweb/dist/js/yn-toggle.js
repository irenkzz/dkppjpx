(function (window, $) {
  if (!$) return;

  function setState($wrap, value) {
    var yesVal = String($wrap.data('yes') || 'Y');
    var noVal = String($wrap.data('no') || 'N');
    var target = value === noVal ? noVal : yesVal;
    var $input = $wrap.find('input[type="hidden"]').first();
    $input.val(target);
    $wrap.find('.yn-yes').toggleClass('active', target === yesVal);
    $wrap.find('.yn-no').toggleClass('active', target === noVal);
  }

  function bindToggle($wrap) {
    if (!$wrap.length) return;
    var isDisabled = $wrap.hasClass('yn-toggle-disabled') || String($wrap.data('disabled')) === '1';

    setState($wrap, $wrap.find('input[type="hidden"]').first().val());

    if (isDisabled) {
      $wrap.addClass('yn-toggle-disabled');
      return;
    }

    if ($wrap.data('ynToggleBound')) return;
    $wrap.data('ynToggleBound', true);

    $wrap.on('click', '.yn-yes', function (e) {
      e.preventDefault();
      setState($wrap, $wrap.data('yes') || 'Y');
    });

    $wrap.on('click', '.yn-no', function (e) {
      e.preventDefault();
      setState($wrap, $wrap.data('no') || 'N');
    });
  }

  function initAll(context) {
    var $ctx = context ? $(context) : $(document);
    $ctx.find('.yn-toggle').each(function () {
      bindToggle($(this));
    });
  }

  function onReady(fn) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setTimeout(fn, 0);
    } else {
      document.addEventListener('DOMContentLoaded', fn, false);
    }
  }

  onReady(function () {
    try { initAll(); } catch (e) { if (window.console && console.warn) console.warn(e); }
  });

  window.initYnToggles = initAll;
  window.setYnToggleValue = function (target, value) {
    var $wrap = $(target);
    if ($wrap.length) {
      setState($wrap, value);
    }
  };
})(window, window.jQuery);
