<ol class="highlight-trending">
  {foreach from=$highlight_trending item=id_product}
    <li>#{$id_product|intval}</li>
  {/foreach}
</ol>
