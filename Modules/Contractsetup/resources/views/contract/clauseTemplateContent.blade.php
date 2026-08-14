<div style="padding: 10px">
    @if (isset($categorys) && !empty($categorys))
        @foreach ($categorys as $category)
            @if($category->required == 1)
             <h6>
                 {{ $category->category_name }}
             </h6>
                 @if (isset($lists) && !empty($lists))
                     <div id="clause_{{$category->category_id}}">
                         @foreach ($lists as $keyl => $list)
                             @if ($list->category == $category->category_id)
                                <p style="padding-left:15px" id="{{ 'clause_item_'.$list->category.'_'.$keyl}}">{{ $list->field_default_value }}</p>
                             @endif
                         @endforeach
                     </div>
                 @endif
            @endif
        @endforeach
    @endif
</div>
