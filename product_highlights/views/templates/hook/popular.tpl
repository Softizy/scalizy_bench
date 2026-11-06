<ol class="highlight-popular">
  {foreach from=$highlight_popular_products item=product}
    <li>{$product.name|escape:'htmlall':'UTF-8'}</li>
  {/foreach}
</ol>
