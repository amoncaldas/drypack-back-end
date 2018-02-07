@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Unidadedetrabalho</div>
                    <div class="panel-body">

                        <a href="{{ url('/unidade-de-trabalho/create') }}" class="btn btn-primary btn-xs" title="Add New UnidadeDeTrabalho"><span class="glyphicon glyphicon-plus" aria-hidden="true"/></a>
                        <br/>
                        <br/>
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>ID</th><th> Name </th><th> Descricao </th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($unidadedetrabalho as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->name }}</td><td>{{ $item->descricao }}</td>
                                        <td>
                                            <a href="{{ url('/unidade-de-trabalho/' . $item->id) }}" class="btn btn-success btn-xs" title="View UnidadeDeTrabalho"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"/></a>
                                            <a href="{{ url('/unidade-de-trabalho/' . $item->id . '/edit') }}" class="btn btn-primary btn-xs" title="Edit UnidadeDeTrabalho"><span class="glyphicon glyphicon-pencil" aria-hidden="true"/></a>
                                            {!! Form::open([
                                                'method'=>'DELETE',
                                                'url' => ['/unidade-de-trabalho', $item->id],
                                                'style' => 'display:inline'
                                            ]) !!}
                                                {!! Form::button('<span class="glyphicon glyphicon-trash" aria-hidden="true" title="Delete UnidadeDeTrabalho" />', array(
                                                        'type' => 'submit',
                                                        'class' => 'btn btn-danger btn-xs',
                                                        'title' => 'Delete UnidadeDeTrabalho',
                                                        'onclick'=>'return confirm("Confirm delete?")'
                                                )) !!}
                                            {!! Form::close() !!}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            <div class="pagination-wrapper"> {!! $unidadedetrabalho->render() !!} </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection