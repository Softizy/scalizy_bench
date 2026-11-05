<ol class="product-ranking">
  {foreach from=$product_ranking item=id_product}
    <li>#{$id_product|intval}</li>
  {/foreach}
</ol>
