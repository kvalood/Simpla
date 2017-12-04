{* Вкладки *}
{capture name=tabs}
    {if in_array('settings', $manager->permissions)}
        <li><a href="index.php?module=SettingsAdmin">Настройки</a></li>
    {/if}
    {if in_array('currency', $manager->permissions)}
        <li><a href="index.php?module=CurrencyAdmin">Валюты</a></li>
    {/if}
    <li class="active"><a href="index.php?module=DeliveriesAdmin">Доставка</a></li>
    {if in_array('payment', $manager->permissions)}
        <li><a href="index.php?module=PaymentMethodsAdmin">Оплата</a></li>
    {/if}
    {if in_array('managers', $manager->permissions)}
        <li><a href="index.php?module=ManagersAdmin">Менеджеры</a></li>
    {/if}
{/capture}

{if $delivery->id}
    {$meta_title = $delivery->name scope=root}
{else}
    {$meta_title = 'Новый способ доставки' scope=root}
{/if}

{include file='tinymce_init.tpl'}

{if $message_success}
    <div class="message message_success">
        <span class="text">
            {if $message_success == 'added'}Способ доставки добавлен{elseif $message_success == 'updated'}Способ доставки изменен{/if}
        </span>
        {if $smarty.get.return}
            <a class="button" href="{$smarty.get.return}">Вернуться</a>
        {/if}
    </div>
{/if}

{if $message_error}
    <div class="message message_error">
        <span class="text">{if $message_error == 'empty_name'}Не указано название доставки{/if}</span>
        {if $smarty.get.return}
            <a class="button" href="{$smarty.get.return}">Вернуться</a>
        {/if}
    </div>
{/if}

<form method="post" id="product">
    <input type="hidden" name="session_id" value="{$smarty.session.id}">

    <div id="name">
        <input class="name" name="name" type="text" value="{$delivery->name|escape}" placeholder="Название способа доставки" required>
        <input name="id" type="hidden" value="{$delivery->id}">
        <div class="checkbox">
            <input id="active_checkbox" name="enabled" value="1" type="checkbox"{if $delivery->enabled} checked{/if}/>
            <label for="active_checkbox">Активен</label>
        </div>
    </div>

    <div id="column_left">
        <div class="block layer">
            <h2>Стоимость доставки</h2>
            <ul>
                <li>
                    <label for="price" class="property">Стоимость</label>
                    <input id="price" name="price" class="simpla_small_inp" type="text" value="{$delivery->price}"/> {$currency->sign}
                </li>
                <li>
                    <label for="free_from" class="property">Бесплатна от</label>
                    <input id="free_from" name="free_from" class="simpla_small_inp" type="text" value="{$delivery->free_from}"/> {$currency->sign}
                </li>
                <li>
                    <label for="separate_payment" class="property">Оплачивается отдельно</label>
                    <input id="separate_payment" name="separate_payment" type="checkbox" value="1" {if $delivery->separate_payment} checked{/if} />
                </li>
            </ul>
        </div>
    </div>

    <div id="column_right">
        <div class="block layer">
            <h2>Возможные способы оплаты</h2>
            <ul>
                {foreach $payment_methods as $payment_method}
                    <li>
                        <input id="payment_{$payment_method->id}" type="checkbox" name="delivery_payments[]" value="{$payment_method->id}" {if in_array($payment_method->id, $delivery_payments)}checked{/if}>
                        <label for="payment_{$payment_method->id}">{$payment_method->name}</label>
                        <br>
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>

    <div class="block layer">
        <h2>Описание</h2>
        <textarea placeholder="" name="description" class="editor_small">{$delivery->description|escape}</textarea>
    </div>

    <input class="button_green button_save" type="submit" name="save" value="Сохранить"/>
</form>
