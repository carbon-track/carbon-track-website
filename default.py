import json

def format_countries(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        countries = json.load(f)

    formatted_countries = []
    for country in countries:
        states = [
            {
                "name": state["name"], 
                "code": country["iso2"] + "-" + state["state_code"]  # Combine country code and state code
            } 
            for state in country["states"]
        ]
        formatted_countries.append({"name": country["name"], "code": country["iso2"], "states": states})

    return formatted_countries

# Example usage (same as before)
formatted_data = format_countries("countries+states.txt")

# Save the formatted data to a new JSON file
with open("formatted_countries_states.json", 'w', encoding='utf-8') as f:
    json.dump(formatted_data, f, indent=2, ensure_ascii=False)
