<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\{Medico, Paciente, Consulta};
use App\Http\Requests\ConsultaRequest;

class ConsultaController extends Controller
{
    public function data($id)
    {
    	$medico = Medico::where('usuario_id', $id)->first();

    	if($medico->ferias)
    		return redirect('medicos')->withErro($medico->usuario->nome . ' está de férias!');

    	return view('consulta.data', compact('medico'));
    }

    public function horarios($id)
    {
    	$medico = Medico::where('usuario_id', $id)->first();

    	if($medico->ferias)
    		return redirect('medicos')->withErro($medico->usuario->nome . ' está de férias!');

    	if(!isset($_GET['data']))
    		return redirect('medicos/'.$id.'/consulta/data')->withErro('Por favor, selecione uma data.');

    	if(strtotime($_GET['data'] . ' 23:59:59') < strtotime( date('Y-m-a') . ' 23:59:59' ))
    		return redirect('medicos/'.$id.'/consulta/data')->withErro('Por favor, selecione uma data posterior ou igual a hoje.');

        if(!$medico->carga_horaria)
            return redirect('medicos')->withErro($medico->usuario->nome . ' não tem uma carga horária!');

    	$inicio = new \DateTime($_GET['data'] . ' ' . $medico->carga_horaria->inicio);
        $fim = new \DateTime($_GET['data'] . ' ' . $medico->carga_horaria->fim);
		$agora = new \DateTime(date('Y-m-d H:i'));

		if($inicio > $fim)
			$fim->add( new \DateInterval('P1D') );

		$intervalo = new \DateInterval('PT'.$medico->carga_horaria->intervalo.'M');
		$periodo = new \DatePeriod($inicio, $intervalo ,$fim);

        $consultas = [];
        foreach ($medico->consultas as $key => $value) {
            $consultas[] = date('Y-m-d H:i', strtotime($value->horario));
        }


    	return view('consulta.horarios', compact('medico', 'inicio', 'agora', 'fim', 'periodo', 'consultas'));
    }

    public function marcar($id)
    {
        $medico = Medico::where('usuario_id', $id)->first();

        if($medico->ferias)
            return redirect('medicos')->withErro($medico->usuario->nome . ' está de férias!');

        if(!isset($_GET['horario']))
            return redirect('medicos')->withErro('Sua consulta não tinha um horário válido, recomeçe.');

        if(!isset($_GET['q']))
            $pacientes = Paciente::paginate( config('prontuario.paginacao') );
        else {
            $pacientes = Paciente::where('nome', 'like', '%'.$_GET['q'].'%')
                ->orWhere('email', 'like', '%'.$_GET['q'].'%')
                ->orWhere('cpf', 'like', '%'.$_GET['q'].'%')
                ->orWhere('prontuario', 'like', '%'.$_GET['q'].'%')
                ->orWhere('nascimento', 'like', '%'.$_GET['q'].'%')
            ->paginate( config('prontuario.paginacao') );
        }

        $pacientes->appends(['horario' => $_GET['horario']]);
        return view('consulta.marcar', compact('medico', 'pacientes'));
    }

    public function finalizar($id)
    {
        $medico = Medico::where('usuario_id', $id)->first();

        if($medico->ferias)
            return redirect('medicos')->withErro($medico->usuario->nome . ' está de férias!');

        if(!isset($_GET['horario']))
            return redirect('medicos')->withErro('Sua consulta não tinha um horário válido, recomeçe.');

        if(!isset($_GET['paciente']))
            return redirect('medicos')->withErro('Sua consulta não tinha um paciente válido, recomeçe.');

        $paciente = Paciente::find($_GET['paciente']);

        if(!$paciente)
            return redirect('medicos')->withErro('Sua consulta não tinha um paciente válido, recomeçe.');

        return view('consulta.finalizar', compact('medico', 'paciente'));
    }

    public function salvar(ConsultaRequest $requisicao, $id)
    {
        $medico = Medico::where('usuario_id', $id)->first();
        $paciente = Paciente::find($requisicao->paciente_id);

        if(!$medico)
            return redirect('medicos')->withErro('Sua consulta não tinha um médico válido, recomeçe.');

        foreach ($medico->consultas as $consulta)
            if($consulta->horarios == $requisicao->horario)
                return redirect('medicos')->withErro($medico->usuario->nome . ' já tem consulta para esse horário!');

        if(!$paciente)
            return redirect('medicos')->withErro('Sua consulta não tinha um paciente válido, recomeçe.');

        foreach ($paciente->consultas as $consulta)
            if($consulta->horarios == $requisicao->horario)
                return redirect('medicos')->withErro($paciente->nome . ' já tem consulta para esse horário!');

        $horario = new \DateTime($requisicao->horario);
        $agora = new \DateTime(date('Y-m-d H:i'));

        if($horario < $agora)
            return redirect('medicos')->withErro('Horário da consulta já passou');

        if($medico->ferias)
            return redirect('medicos')->withErro($medico->usuario->nome . ' está de férias!');

        
        Consulta::create($requisicao->all());

        return redirect('medicos/'.$id.'/consultas')->withMsg('Consulta marcada em '.$horario->format('d/m/Y á\s H:i') . ' para '.$medico->usuario->nome);
    }

    public function lista($id)
    {
        $medico = Medico::where('usuario_id', $id)->first();

        if(!isset($_GET['q'])) {
            $consultas = Consulta::where('medico_id', $id)
                ->orderBy('horario', 'desc')
            ->paginate( config('prontuario.paginacao') );
        } 
        else {
            $consultas = Consulta::where('medico_id', $id)
                ->where('horario', 'like', '%'.$_GET['q'].'%')
                ->orWhere('status', 'like', '%'.$_GET['q'].'%')
                ->orWhereHas('paciente', function($query) {
                    $query->where('nome', 'like', '%'.$_GET['q'].'%')
                        ->orWhere('email', 'like', '%'.$_GET['q'].'%')
                        ->orWhere('cpf', 'like', '%'.$_GET['q'].'%');
                })
                ->orderBy('horario', 'desc')
            ->paginate( config('prontuario.paginacao') );
        }

        

        $agora = strtotime('now');
        $depois =  strtotime('+'.$medico->carga_horaria->intervalo.' minutes');

        $inicio = new \DateTime(date('Y-m-d') . ' ' . $medico->carga_horaria->inicio);
        $fim = new \DateTime(date('Y-m-d') . ' ' . $medico->carga_horaria->fim);

        if($inicio > $fim)
            $fim->add( new \DateInterval('P1D') );

        $intervalo = new \DateInterval('PT'.$medico->carga_horaria->intervalo.'M');
        $periodo = new \DatePeriod($inicio, $intervalo ,$fim);

        $ini = $agora;
        foreach($periodo as $data) {
            if($agora <= $data->format('U')) {
                $depois = $data->format('U');
                $ini = $depois - ($medico->carga_horaria->intervalo * 60);
                break;
            }
        }

        $tipo = 'med';

        return view('consulta.lista', compact('agora', 'depois', 'medico', 'consultas', 'tipo', 'ini'));
    }

    public function apagar($id, $consulta)
    {
        $consulta = Consulta::where('id', $consulta)->where('medico_id', $id)->first();

        if($consulta)
            $consulta->delete();

        return redirect($_SERVER['HTTP_REFERER'])->withMsg('Consulta foi apagada!');
    }

    public function atender($id)
    {
        $consulta = Consulta::find($id);

        if(!$consulta)
            return redirect('medicos/dia')->withErro('Consulta não encontrada!');

        $consulta->atendida = 1;
        $consulta->save();

        return redirect($_SERVER['HTTP_REFERER'])->withMsg('Consulta marcada como atendida!');
    }

    public function listaPaciente($id)
    {
        $paciente = Paciente::find($id);

        if(!isset($_GET['q'])) {
            $consultas = Consulta::where('paciente_id', $id)
                ->orderBy('horario', 'desc')
            ->paginate( config('prontuario.paginacao') );
        } 
        else {
            $consultas = Consulta::where('paciente_id', $id)
                ->where('horario', 'like', '%'.$_GET['q'].'%')
                ->orWhere('status', 'like', '%'.$_GET['q'].'%')
                ->orWhereHas('paciente', function($query) {
                    $query->where('nome', 'like', '%'.$_GET['q'].'%')
                        ->orWhere('email', 'like', '%'.$_GET['q'].'%')
                        ->orWhere('cpf', 'like', '%'.$_GET['q'].'%');
                })
                ->orderBy('horario', 'desc')
            ->paginate( config('prontuario.paginacao') );
        }

        $tipo = 'pac';

        return view('consulta.lista', compact('tipo', 'consultas', 'paciente'));
    }
}