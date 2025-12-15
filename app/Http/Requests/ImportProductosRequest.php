<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductosRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'archivo_excel' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240' // 10MB
            ],
            'empresa_id' => [
                'nullable',
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
            'archivo_excel.required' => 'El archivo Excel es obligatorio.',
            'archivo_excel.file' => 'Debe seleccionar un archivo válido.',
            'archivo_excel.mimes' => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV.',
            'archivo_excel.max' => 'El archivo no puede superar los 10MB.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'modo_importacion.required' => 'Debe seleccionar un modo de importación.',
            'modo_importacion.in' => 'El modo de importación debe ser: crear, actualizar o crear_actualizar.'
        ];
    }
}
