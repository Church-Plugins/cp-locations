import InputBase from '@mui/material/InputBase';
import { Search } from '@mui/icons-material';

export default function SearchInput({
  onValueChange,
  // "short", "full"
  width = "short",
}) {
	const urlParams = new URLSearchParams(window.location.search);

  return (
    <InputBase
      className="searchInput__root"
      placeholder="Enter your zip code"
      defaultValue={urlParams.get('s')}
      startAdornment={<Search />}
      inputProps={{pattern: '[0-9]{5}', maxLength: '5'}}
      onChange={e => onValueChange(e.target.value)}
    />
  );
}
