New website enquiry ({!! $enquiry->category->label() !!})

From: {!! $enquiry->name !!} <{!! $enquiry->email !!}>
@if ($enquiry->phone)Phone: {!! $enquiry->phone !!}
@endif
@if ($enquiry->organisation)Organisation: {!! $enquiry->organisation !!}
@endif

{!! $enquiry->message !!}

--
Reply to this email to answer the sender, or manage it in the admin: {!! url(config('admin.path').'/enquiries/'.$enquiry->id) !!}
