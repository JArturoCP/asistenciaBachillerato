<div class="row g-3">
@foreach($permissions as $group => $items)
<div class="col-md-6 col-lg-4"><div class="border rounded p-3 h-100"><div class="fw-bold mb-2">{{ $group }}</div>
@foreach($items as $permission)<label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->code }}" id="{{ $prefix }}-{{ $permission->id }}" @checked(in_array($permission->code,$selected,true))><span class="form-check-label small">{{ $permission->name }}</span></label>@endforeach
</div></div>@endforeach
</div>