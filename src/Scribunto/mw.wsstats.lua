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

-- wsstats stats
function wsstats.wsstats( id, unique, startDate, endDate, limit, title )

    return php.wsstats( id, unique, startDate, endDate, limit, title )
end


return wsstats
