{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  {block name="header"}
    <h2>
      <a id="infoLink" href="{$infoURL}">
        <img class="infoLink" src="/common/images/info_button@2x.png" alt="get info" width="44" height="38" />
      </a>
      {$location['name']}
      {if $location['primaryname'] != $location['name']}<br/>({$location['primaryname']}){/if}
    </h2>
    <span class="distance">
      {if $location['hours'] && $location['hours'] != 'closed'}
        Open today {$location['hours']}
      {else}
        Closed today
      {/if}
    </span><br/>
  {/block}
</div>

{foreach $location['collections'] as $collection}
  <div class="nonfocal itemInfo">
    <h3>{$collection['name']}</h3>
    {if $collection['callNumber']}<span class="smallprint">{$collection['callNumber']}</span>{/if}
  {foreach $collection['categories'] as $category}
    {if !$category@first}<div class="nonfocal itemInfo">{/if}
      {if $category['type'] != 'collection'}<br/><span class="smallprint">{$category['type']}</span>{/if}
    </div>
    
    {$list = array()}
    {foreach $category['items'] as $item}
      {$listItem = array()}
      {capture name="title" assign="title"}
        {if $item['state'] == 'available'}
          {$class = 'available'}
        {elseif $item['state'] == 'unavailable' || $category['type'] == 'collection'}
          {$class = 'unavailable'}
        {else}
          {$class = 'requestable'}
        {/if}
        {block name="itemTitle"}
          <span class="itemType {$class}">
            {$item['count']}
            {if $item['state'] == 'collection'}
              may be available
            {else}
              {$item['state']}
            {/if}
            {if $category['type'] != 'collection'}
              {if $item['status']}({$item['status']}){/if}
            {else}
              {if $item['message']}({$item['message']}){/if}
            {/if}
          </span>
          <span class="itemType"><span class="smallprint">
            {if $item['callNumber']}{$item['callNumber']}{if $item['description']}<br/>{/if}{/if}
            {if $item['description']}{$item['description']}{/if}
          </span></span>
        {/block}
      {/capture}
      {$listItem['title'] = $title}
      {if $item['url']}
        {$listItem['url'] = $item['url']}
      {/if}
      {block name="extraItemInfo"}
      {/block}
      {$list[] = $listItem}
    {/foreach}
    {if count($list)}
      <div class="items">
        {include file="findInclude:common/navlist.tpl" navlistItems=$list subTitleNewline=false accessKey=false labelColon=false}
      </div>
    {/if}
  {/foreach}
{/foreach}

{include file="findInclude:common/footer.tpl"}
