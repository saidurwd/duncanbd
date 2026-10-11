New job application

Vacancy: {!! $application->vacancy->title !!}
Candidate: {!! $application->name !!} <{!! $application->email !!}>
@if ($application->phone)Phone: {!! $application->phone !!}
@endif

Review the application and download the CV in the admin: {!! url(config('admin.path').'/careers/applications/'.$application->id) !!}
