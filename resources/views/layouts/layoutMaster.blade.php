@isset($pageConfigs)
    {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = Helper::appClasses(); //statically call the class and in it pass the layout type
@endphp

@isset($configData['layout'])
    @include(
        $configData['layout'] === 'horizontal'
            ? 'layouts.horizontalLayout'
            : ($configData['layout'] === 'blank'
                ? 'layouts.blankLayout'
                : ($configData['layout'] === 'front'
                    ? 'layouts.layoutFront'
                    : 'layouts.contentNavbarLayout')))
@endisset

@php
    /*

if (isset($configData['layout'])) {
    if ($configData['layout'] === 'horizontal') {
        include 'layouts.horizontalLayout';
    } elseif ($configData['layout'] === 'blank') {
        include 'layouts.blankLayout';
    } elseif ($configData['layout'] === 'front') {
        include 'layouts.layoutFront';
    } else {
        include 'layouts.contentNavbarLayout';
    }
}
*/
@endphp
