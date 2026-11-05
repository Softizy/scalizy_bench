<ul class="featured-product-slots">
  {foreach from=$featured_product_slots item=slot}
    <li>{$slot.name|escape:'htmlall':'UTF-8'}</li>
  {/foreach}
</ul>
