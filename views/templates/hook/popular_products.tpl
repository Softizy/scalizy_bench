<ul class="popular-products">
  {foreach from=$popular_products item=product}
    <li>{$product.name|escape:'html':'UTF-8'} ({$product.views|intval})</li>
  {/foreach}
</ul>
