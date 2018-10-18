$(function () {
  const details = [
    'Installing database',
    'Validating GPG keys',
    'Setting up keys',
    'Collecting fairy dust',
    'Setting up SMTP',
    'Locating Elon Musk\'s car. Don\'t panic.',
    'Checking options',
    'Writing configuration file',
    'Brewing pale ale',
    'Checking status'
  ];
  let displayStatusTimeout;

  /**
   * Display status.
   */
  function displayStatus(i) {
    $('.install-details').text(details[i % details.length]);
    displayStatusTimeout = setTimeout(() => {
      i++;
      displayStatus(i);
    }, 1000);
  }

  /**
   * Request the API to install passbolt
   */
  async function install() {
    displayStatus(0);
    const installUrl = $('#install-url').val() + '.json';
    const response = await fetch(installUrl);
    clearTimeout(displayStatusTimeout);
    const json = await response.json();
    if (response.ok) {
      handleInstallSuccess(json);
    } else {
      handleInstallError(json);
    }
  }

  /**
   * Handle install success response
   * @param response
   */
  function handleInstallSuccess(response) {
    const bases = document.getElementsByTagName('base');
    const baseUrl = bases[0] ? bases[0].href : '/';
    let redirectUrl = baseUrl;
    if (response.token) {
      redirectUrl = `${baseUrl}setup/install/${response.token.user_id}/${response.token.token}`;
    }

    $('li.selected').removeClass('selected');
    $('li.disabled').removeClass('disabled').addClass('selected');
    $('#js_step_title').text('You\'ve made it!');
    $('#js-install-installing').hide();
    $('#js-install-complete').show();
    $('#js-install-complete-redirect').attr('href', redirectUrl);

    setTimeout(function () {
      document.location.href = redirectUrl;
    }, 5000);
  }

  /**
   * Handle install error response
   * @param response
   */
  function handleInstallError(response) {
    $('#js_step_title').text('Oops something went wrong!');
    $('#js-install-installing').hide();
    $('#js-install-error').show();
    $('#js-install-error-message').text(response.header.message);
    $('#js-install-error-message').text(response.header.message);
    $('#js-install-error-details').text(JSON.stringify(response.body, null, 2));
    $('#js-show-debug-info').on('click', () => $('#js-install-error-details').toggle())
  }

  install();
});