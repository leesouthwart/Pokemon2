<div>
    @foreach($card_groups as $card_group)
    <a href="{{route('card_group.single', $card_group->id)}}">{{$card_group->name}}</a>
    @endforeach
</div>
