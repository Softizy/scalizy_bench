<ul class="highlight-featured">
  {foreach from=$highlight_featured_slots item=slot}
    <li>{$slot.name|escape:'htmlall':'UTF-8'}</li>
  {/foreach}
</ul>
