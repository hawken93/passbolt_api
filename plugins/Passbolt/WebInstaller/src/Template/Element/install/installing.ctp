<? use Cake\Routing\Router; ?>
<div id="js-install-installing" class="grid grid-responsive-12">
    <div class="row">
        <div class="col7">
            <h3><?= __('Installing') ?></h3>
            <div class="progress-bar-wrapper">
                <span class="progress-bar big infinite"><span class="progress "></span></span>
            </div>

            <p class="install-details">Installing database</p>
            <input type="hidden" name="install" id="install-url" value="<?= Router::url($stepInfo['install'], true) ?>">
            <input type="hidden" name="redirect" id="redirect-url" value="<?= Router::url($redirectUrl, true) ?>">
        </div>
        <div class="col5 last">
        </div>
    </div>
    <div class="row last">
        <div class="input-wrapper">
            <a href="#" class="button primary next big processing">next</a>
        </div>
    </div>
</div>