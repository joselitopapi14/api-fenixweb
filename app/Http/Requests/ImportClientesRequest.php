<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportClientesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo_excel' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240' // 10MB máximo
            ],
            'empresa_id' => [
                'required',
                'exists:empresas,id'
            ],
            'modo_importacion' => [
                'required',
                'in:crear,actualizar,crear_actualizar'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'archivo_excel.required' => 'Debe seleccionar un archivo para importar.',
            'archivo_excel.file' => 'El archivo seleccionado no es válido.',
            'archivo_excel.mimes' => 'El archivo debe ser de tipo: Excel (.xlsx, .xls) o CSV (.csv).',
            'archivo_excel.max' => 'El archivo no puede ser mayor a 10MB.',
            'empresa_id.required' => 'Debe seleccionar una empresa.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'modo_importacion.required' => 'Debe seleccionar un modo de importación.',
            'modo_importacion.in' => 'El modo de importación seleccionado no es válido.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'archivo_excel' => 'archivo de Excel',
            'empresa_id' => 'empresa',
            'modo_importacion' => 'modo de importación',
        ];
    }
}
