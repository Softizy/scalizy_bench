<ul class="scalizy-bench-footer">
  {foreach from=$scalizy_bench_items item=item}
    <li>{$item.name|escape:'htmlall':'UTF-8'}</li>
  {/foreach}
</ul>
