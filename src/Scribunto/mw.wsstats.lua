-- Variable instantiation
local wsstats = {}
local php

function wsstats.setupInterface()
    -- Interface setup
    wsstats.setupInterface = nil
    php = mw_interface
    mw_interface = nil

    -- Register library within the "mw.slots" namespace
    mw = mw or {}
    mw.wsstats = wsstats

    package.loaded['mw.wsstats'] = wsstats
end

-- slotContent
function wsstats.slotContent( slotName, pageName )
    if not type( slotName ) == 'string' or not type( pageName ) == 'string' or not type( pageName ) == 'nil' then
        error( 'Invalid parameter type supplied to mw.slots.slotContent()' )
    end

    return php.slotContent( slotName, pageName )
end

-- slotTemplates
function wsstats.slotTemplates( slotName, pageName )
    if not type( slotName ) == 'string' or not type( pageName ) == 'string' or not type( pageName ) == 'nil' then
        error( 'Invalid parameter type supplied to mw.slots.slotTemplates()' )
    end

    return php.slotTemplates( slotName, pageName )
end

-- slotContentModel
function wsstats.slotContentModel( slotName, pageName )
    if not type( slotName ) == 'string' or not type( pageName ) == 'string' or not type( pageName ) == 'nil' then
        error( 'Invalid parameter type supplied to mw.slots.slotContentModel()' )
    end

    return php.slotContentModel( slotName, pageName )
end

-- slotData
function wsstats.slotData( slotName, pageName )
    if not type( slotName ) == 'string' or not type( pageName ) == 'string' or not type( pageName ) == 'nil' then
        error( 'Invalid parameter type supplied to mw.slots.slotContentModel()' )
    end

    return php.slotData( slotName, pageName )
end

return slots
